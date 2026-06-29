<?php
namespace App\Utils;

use DateTime;

class GpxParser {
    
    /**
     * Parses a GPX file and returns statistics as well as downsampled data.
     */
    public static function parse(string $filePath): ?array {
        if (!file_exists($filePath)) {
            return null;
        }

        $xml = simplexml_load_file($filePath);
        if ($xml === false || !isset($xml->trk)) {
            return null;
        }

        $segments = [];
        foreach ($xml->trk as $trk) {
            foreach ($trk->trkseg as $trkseg) {
                $segment = [];
                foreach ($trkseg->trkpt as $pt) {
                    $lat = (float) $pt['lat'];
                    $lon = (float) $pt['lon'];
                    $timeStr = (string) $pt->time;
                    if (empty($timeStr)) continue;
                    
                    $time = new DateTime($timeStr);
                    $segment[] = [
                        'lat' => $lat,
                        'lon' => $lon,
                        'time' => $time->getTimestamp()
                    ];
                }
                if (count($segment) > 1) {
                    $segments[] = $segment;
                }
            }
        }

        if (empty($segments)) {
            return null;
        }

        $totalDistance = 0;
        $totalDuration = 0;
        $maxSpeed = 0;
        
        $mapPoints = [];
        $speedPoints = [];

        $lastMapTime = 0;
        $lastSpeedTime = 0;
        
        $overallStartTime = null;
        $overallEndTime = null;

        foreach ($segments as $segment) {
            $segmentStart = $segment[0]['time'];
            $segmentEnd = $segment[count($segment) - 1]['time'];
            
            if ($overallStartTime === null || $segmentStart < $overallStartTime) {
                $overallStartTime = $segmentStart;
            }
            if ($overallEndTime === null || $segmentEnd > $overallEndTime) {
                $overallEndTime = $segmentEnd;
            }

            $lastRefPoint = $segment[0];

            for ($i = 1; $i < count($segment); $i++) {
                $p = $segment[$i];

                // Map downsampling: keep drawing the track at the configured interval
                if ($p['time'] - $lastMapTime >= MAP_POINT_INTERVAL) {
                    $mapPoints[] = [$p['lat'], $p['lon']];
                    $lastMapTime = $p['time'];
                }

                $timeDiff = $p['time'] - $lastRefPoint['time'];
                $isLastPoint = ($i === count($segment) - 1);

                // Calculate stats strictly in configured slices (or the final slice of the segment)
                if ($timeDiff >= STATS_CALC_INTERVAL || $isLastPoint) {
                    // Ignore absurd gaps like > 1 hour
                    if ($timeDiff <= 3600 && $timeDiff > 0) {
                        $dist = self::haversine($lastRefPoint['lat'], $lastRefPoint['lon'], $p['lat'], $p['lon']);
                        
                        $totalDuration += $timeDiff;
                        $totalDistance += $dist; // in meters

                        $speedMs = $dist / $timeDiff;
                        $speedKnots = $speedMs * 1.94384;

                        if ($speedKnots > $maxSpeed && $speedKnots <= 45) {
                            $maxSpeed = $speedKnots;
                        }

                        $speedPoints[] = [
                            't' => $p['time'] * 1000,
                            'v' => round($speedKnots, 1),
                            'lat' => $p['lat'],
                            'lon' => $p['lon']
                        ];
                    }
                    
                    // Set the new reference point for the next 10-minute slice
                    $lastRefPoint = $p;
                }
            }
        }

        if ($totalDuration == 0) {
            return null; // Invalid or completely skipped tracks
        }

        $avgSpeedKnots = 0;
        if ($totalDuration > 0) {
            $avgSpeedMs = $totalDistance / $totalDuration;
            $avgSpeedKnots = $avgSpeedMs * 1.94384;
        }

        return [
            'start_time' => date('Y-m-d H:i:s', $overallStartTime),
            'end_time' => date('Y-m-d H:i:s', $overallEndTime),
            'distance_meters' => round($totalDistance, 2),
            'duration_seconds' => $totalDuration,
            'avg_speed_knots' => round($avgSpeedKnots, 2),
            'max_speed_knots' => round($maxSpeed, 2),
            'map_points' => $mapPoints,
            'speed_points' => $speedPoints
        ];
    }

    /**
     * Haversine formula to calculate the distance between two coordinates (in meters)
     */
    private static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $earthRadius = 6371000; // Earth radius in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}
