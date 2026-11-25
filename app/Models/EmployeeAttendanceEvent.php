<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAttendanceEvent extends Model
{
    use HasFactory;

    protected $table = 'employee_attendance_events';

    // Event Types
    public const TYPE_CHECK_IN = 'check_in';
    public const TYPE_TEMPORARY_CHECK_OUT = 'temporary_check_out';
    public const TYPE_RETURN_CHECK_IN = 'return_check_in';
    public const TYPE_FINAL_CHECK_OUT = 'final_check_out';

    // Event Sources
    public const SOURCE_WEB = 'web';
    public const SOURCE_MOBILE = 'mobile';
    public const SOURCE_BIOMETRIC = 'biometric';
    public const SOURCE_MANUAL_ADJUSTMENT = 'manual_adjustment';
    public const SOURCE_SYSTEM = 'system';

    protected $fillable = [
        'attendance_session_id',
        'attendance_request_id',
        'event_type',
        'event_at',
        'event_source',
        'latitude',
        'longitude',
        'location_accuracy',
        'device_identifier',
        'reason',
        'discrepancy_id',
    ];

    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'location_accuracy' => 'float',
            'discrepancy_id' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendanceSession::class, 'attendance_session_id', 'id');
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendanceRequest::class, 'attendance_request_id', 'id');
    }

    public function adjustmentsAsOriginal(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceAdjustment::class, 'original_event_id', 'id');
    }

    public function adjustmentsAsCreated(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceAdjustment::class, 'created_event_id', 'id');
    }

    /**
     * Hitung jarak (dalam meter) menggunakan formula Haversine.
     */
    public static function calculateDistanceInMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meter

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validasi apakah lokasi koordinat berada dalam batas radius sekolah (default 1 km / 1000m).
     */
    public static function isWithinAllowedRadius(float $latitude, float $longitude): bool
    {
        $schoolLat = (float) config('attendance.school_latitude', -6.2088);
        $schoolLon = (float) config('attendance.school_longitude', 106.8456);
        $maxRadius = (float) config('attendance.max_radius_meters', 1000);

        $distance = self::calculateDistanceInMeters($latitude, $longitude, $schoolLat, $schoolLon);

        return $distance <= $maxRadius;
    }
}
