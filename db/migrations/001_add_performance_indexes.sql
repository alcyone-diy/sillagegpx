-- Performance indexes for trip queries and aggregated distance stats
CREATE INDEX IF NOT EXISTS idx_trips_user_id ON trips(user_id);
CREATE INDEX IF NOT EXISTS idx_trip_steps_trip_id ON trip_steps(trip_id);
CREATE INDEX IF NOT EXISTS idx_gpx_tracks_step_id ON gpx_tracks(trip_step_id);
