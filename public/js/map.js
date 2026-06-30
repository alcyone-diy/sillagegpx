document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.TRIP_DATA === 'undefined' || window.TRIP_DATA.length === 0) {
        return;
    }

    const map = L.map('map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        className: 'map-tiles'
    }).addTo(map);

    let mapPolylines = [];
    let speedChartInstance = null;
    
    // Palette for different steps
    const palette = [
        '14, 165, 233',   // blue
        '16, 185, 129',   // green
        '245, 158, 11',   // amber
        '236, 72, 153',   // pink
        '139, 92, 246',   // purple
        '239, 68, 68',    // red
        '6, 182, 212',    // cyan
        '132, 204, 22'    // lime
    ];

    // Group tracks by Day
    const tracksByDay = {};
    window.TRIP_DATA.forEach(trackInfo => {
        const startTime = trackInfo.stats.start_time;
        if (!startTime) return;
        const isoDate = startTime.split(' ')[0];
        if (!tracksByDay[isoDate]) {
            tracksByDay[isoDate] = [];
        }
        tracksByDay[isoDate].push(trackInfo);
    });

    const days = Object.keys(tracksByDay).sort();

    function renderView(dayKey) {
        // Clear existing map layers
        mapPolylines.forEach(layer => map.removeLayer(layer));
        mapPolylines = [];

        if (window.hoverMarker && map.hasLayer(window.hoverMarker)) {
            map.removeLayer(window.hoverMarker);
            window.hoverMarker = null;
        }

        let stats = { distance: 0, duration: 0, maxSpeed: 0 };
        let tracksToRender = [];
        let datasetsMap = {};

        if (dayKey === 'all') {
            days.forEach((dKey, idx) => {
                const color = palette[idx % palette.length];
                datasetsMap[dKey] = { label: `${window.TRIP_LANG.day || 'Day'} ${idx+1}`, color };
                tracksByDay[dKey].forEach(trackInfo => {
                    tracksToRender.push({ trackInfo, color, datasetKey: dKey });
                });
            });
        } else {
            const idx = days.indexOf(dayKey);
            const color = palette[idx % palette.length] || palette[0];
            datasetsMap[dayKey] = { label: `${window.TRIP_LANG.day || 'Day'} ${idx+1}`, color };
            (tracksByDay[dayKey] || []).forEach(trackInfo => {
                tracksToRender.push({ trackInfo, color, datasetKey: dayKey });
            });
        }

        let allSpeedPoints = [];
        let allMapPoints = [];

        tracksToRender.forEach(item => {
            const data = item.trackInfo.data;
            const tStats = item.trackInfo.stats;

            if (data && data.map_points) {
                const latlngs = data.map_points.map(p => [p[0], p[1]]);
                allMapPoints = allMapPoints.concat(latlngs);
                
                const pline = L.polyline(latlngs, {
                    color: `rgb(${item.color})`,
                    weight: 4,
                    opacity: 0.8
                }).addTo(map);

                const dayIndex = days.indexOf(item.datasetKey);
                pline.bindTooltip(`<strong>${window.TRIP_LANG.day || 'Day'} ${dayIndex + 1}</strong>`, { sticky: true });
                
                pline.on('click', () => {
                    const el = document.getElementById(`step-li-${item.datasetKey}`);
                    if (el) el.click();
                });

                pline.on('mouseover', function () {
                    this.setStyle({ weight: 8, opacity: 1 });
                });
                
                pline.on('mouseout', function () {
                    this.setStyle({ weight: 4, opacity: 0.8 });
                });

                mapPolylines.push(pline);
            }

            if (data && data.speed_points) {
                data.speed_points.forEach((p, index) => {
                    let lat = p.lat;
                    let lon = p.lon;
                    if (!lat || !lon) {
                        // Fallback for old tracks: estimate from map_points (ratio 10:1)
                        if (data.map_points) {
                            const mapIdx = Math.min(index * 10, data.map_points.length - 1);
                            if (data.map_points[mapIdx]) {
                                lat = data.map_points[mapIdx][0];
                                lon = data.map_points[mapIdx][1];
                            }
                        }
                    }
                    allSpeedPoints.push({ t: p.t, v: p.v, lat: lat, lon: lon, datasetKey: item.datasetKey });
                });
            }

            stats.distance += parseFloat(tStats.distance_meters || 0);
            stats.duration += parseInt(tStats.duration_seconds || 0);
            const ms = parseFloat(tStats.max_speed_knots || 0);
            if (ms > stats.maxSpeed) {
                stats.maxSpeed = ms;
            }
        });

        if (allMapPoints.length > 0) {
            map.fitBounds(L.polyline(allMapPoints).getBounds(), { padding: [20, 20] });
        }

        // Render Stats
        const distanceNm = (stats.distance / 1852).toFixed(1);
        const durationHours = (stats.duration / 3600).toFixed(1);
        const avgSpeed = (stats.duration > 0) ? (distanceNm / durationHours).toFixed(1) : 0;
        
        document.getElementById('globalStats').innerHTML = `
            <div class="stat-box">
                <span class="stat-value">${distanceNm} NM</span>
                <span class="stat-label">${window.TRIP_LANG.distance || 'Distance'}</span>
            </div>
            <div class="stat-box">
                <span class="stat-value">${durationHours} h</span>
                <span class="stat-label">${window.TRIP_LANG.duration || 'Duration'}</span>
            </div>
            <div class="stat-box">
                <span class="stat-value">${avgSpeed} kts</span>
                <span class="stat-label">${window.TRIP_LANG.avgSpeed || 'Avg Speed'}</span>
            </div>
            <div class="stat-box">
                <span class="stat-value">${stats.maxSpeed.toFixed(1)} kts</span>
                <span class="stat-label">${window.TRIP_LANG.maxSpeed || 'Max Speed'}</span>
            </div>
        `;

        // Render Chart
        if (speedChartInstance) {
            speedChartInstance.destroy();
            speedChartInstance = null;
        }

        allSpeedPoints.sort((a, b) => new Date(a.t) - new Date(b.t));

        let labels = [];
        let datasetsArrayData = {};
        let datasetsArrayPoints = {};
        Object.keys(datasetsMap).forEach(k => {
            datasetsArrayData[k] = [];
            datasetsArrayPoints[k] = [];
        });

        let lastTime = null;

        allSpeedPoints.forEach(p => {
            const d = new Date(p.t);
            
            if (lastTime && (d.getTime() - lastTime > 30 * 60 * 1000)) {
                labels.push('...');
                Object.keys(datasetsMap).forEach(k => {
                    datasetsArrayData[k].push(null);
                    datasetsArrayPoints[k].push(null);
                });
            }
            lastTime = d.getTime();

            labels.push(d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}));
            Object.keys(datasetsMap).forEach(k => {
                if (k === p.datasetKey) {
                    datasetsArrayData[k].push(p.v);
                    datasetsArrayPoints[k].push(p);
                } else {
                    datasetsArrayData[k].push(null);
                    datasetsArrayPoints[k].push(null);
                }
            });
        });

        const chartDatasets = Object.keys(datasetsMap).map(k => {
             const info = datasetsMap[k];
             return {
                 label: info.label,
                 data: datasetsArrayData[k],
                 borderColor: `rgb(${info.color})`,
                 backgroundColor: `rgba(${info.color}, 0.1)`,
                 borderWidth: 2,
                 fill: true,
                 tension: 0.4,
                 pointRadius: 0,
                 pointHitRadius: 10,
                 spanGaps: false
             };
        });

        if (allSpeedPoints.length > 0 && document.getElementById('speedChart')) {
            const ctx = document.getElementById('speedChart').getContext('2d');
            Chart.defaults.color = '#8b9bb4';
            Chart.defaults.font.family = 'Inter';
            
            speedChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: chartDatasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    onHover: (event, activeElements) => {
                        if (activeElements.length > 0) {
                            let foundPoint = null;
                            for (let i = 0; i < activeElements.length; i++) {
                                const active = activeElements[i];
                                const datasetKey = Object.keys(datasetsMap)[active.datasetIndex];
                                const point = datasetsArrayPoints[datasetKey][active.index];
                                if (point && point.lat && point.lon) {
                                    foundPoint = point;
                                    break;
                                }
                            }

                            if (foundPoint) {
                                if (!window.hoverMarker) {
                                    window.hoverMarker = L.circleMarker([foundPoint.lat, foundPoint.lon], {
                                        radius: 6,
                                        color: '#fff',
                                        fillColor: '#ef4444',
                                        fillOpacity: 1,
                                        weight: 2
                                    }).addTo(map);
                                } else {
                                    window.hoverMarker.setLatLng([foundPoint.lat, foundPoint.lon]);
                                    if (!map.hasLayer(window.hoverMarker)) {
                                        window.hoverMarker.addTo(map);
                                    }
                                }
                            } else {
                                if (window.hoverMarker && map.hasLayer(window.hoverMarker)) {
                                    map.removeLayer(window.hoverMarker);
                                }
                            }
                        } else {
                            if (window.hoverMarker && map.hasLayer(window.hoverMarker)) {
                                map.removeLayer(window.hoverMarker);
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(20, 27, 45, 0.9)',
                            titleColor: '#fff'
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { maxTicksLimit: 8 }
                        },
                        y: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false },
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    // Populate Sidebar Steps List
    const stepsList = document.getElementById('stepsList');
    if (stepsList) {
        stepsList.innerHTML = '';
        
        // Add "Overview" item
        const overviewLi = document.createElement('li');
        overviewLi.className = 'step-item active';
        overviewLi.style.cursor = 'pointer';
        overviewLi.innerHTML = `<strong>${window.TRIP_LANG.overview || 'Trip Overview'}</strong><br><small class="text-muted">${days.length} ${window.TRIP_LANG.days || 'Days'}</small>`;
        overviewLi.addEventListener('click', () => {
            document.querySelectorAll('.step-item').forEach(el => {
                el.classList.remove('active');
                if (el !== overviewLi) {
                    el.style.borderColor = 'var(--border-glass)';
                    if (el.dataset.origBorder) {
                        el.style.borderLeft = el.dataset.origBorder;
                    }
                }
            });
            overviewLi.classList.add('active');
            overviewLi.style.borderColor = '';
            renderView('all');
        });
        stepsList.appendChild(overviewLi);

        // Add Steps
        days.forEach((dayKey, index) => {
            const [y, m, d] = dayKey.split('-');
            const dateObj = new Date(y, m - 1, d);
            const formattedDate = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
            
            const colorRgb = palette[index % palette.length];
            
            const li = document.createElement('li');
            li.id = `step-li-${dayKey}`;
            li.className = 'step-item';
            li.style.cursor = 'pointer';
            // Set a left border color to match the chart/map
            li.style.borderLeft = `4px solid rgb(${colorRgb})`;
            
            li.innerHTML = `<strong>${window.TRIP_LANG.day || 'Day'} ${index + 1}</strong><br><small class="text-muted">${formattedDate}</small>`;
            li.addEventListener('click', () => {
                document.querySelectorAll('.step-item').forEach(el => {
                    el.classList.remove('active');
                    if(el !== overviewLi) el.style.borderColor = 'var(--border-glass)';
                    if(el !== overviewLi) el.style.borderLeft = el.dataset.origBorder;
                });
                
                // Store original border left so we can restore it when deselected
                if (!li.dataset.origBorder) {
                    li.dataset.origBorder = li.style.borderLeft;
                }
                
                li.classList.add('active');
                li.style.borderColor = `rgb(${colorRgb})`;
                renderView(dayKey);
            });
            
            // Store original border left
            li.dataset.origBorder = `4px solid rgb(${colorRgb})`;
            
            stepsList.appendChild(li);
        });
    }

    // Initial render
    renderView('all');
});
