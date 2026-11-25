<?php

return [
    /*
    |--------------------------------------------------------------------------
    | School Location Coordinates & Geofencing Radius
    |--------------------------------------------------------------------------
    |
    | Presensi hanya diperbolehkan dalam radius maksimal dari lokasi sekolah.
    | Default radius: 1000 meter (1 km).
    |
    */
    'school_latitude' => env('ATTENDANCE_SCHOOL_LATITUDE', -6.2088),
    'school_longitude' => env('ATTENDANCE_SCHOOL_LONGITUDE', 106.8456),
    'max_radius_meters' => env('ATTENDANCE_MAX_RADIUS_METERS', 1000), // 1 km radius
];
