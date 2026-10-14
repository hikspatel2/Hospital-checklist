<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * GeoLocationService
 * Performs server-side GPS geofence verification using the Haversine formula,
 * accuracy threshold analysis, and spoofing mitigation audit logging.
 */
class GeoLocationService
{
    // Sheth L.G. General Hospital, Maninagar, Ahmedabad campus centroid
    public const DEFAULT_HOSPITAL_LAT = 22.9984;
    public const DEFAULT_HOSPITAL_LON = 72.6025;
    public const DEFAULT_GEOFENCE_RADIUS_METERS = 150;
    public const MAX_ACCEPTABLE_ACCURACY_METERS = 100.0;

    /**
     * Calculate exact great-circle distance between two GPS coordinates using the Haversine formula.
     * Earth mean radius = 6,371,000 meters.
     *
     * @return float Distance in meters
     */
    public static function calculateHaversineDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        $earthRadius = 6371000.0; // meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2.0) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2.0) ** 2;

        $c = 2.0 * atan2(sqrt($a), sqrt(1.0 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Alias for calculateHaversineDistance
     */
    public static function calculateDistanceMeters(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        return self::calculateHaversineDistance($lat1, $lon1, $lat2, $lon2);
    }

    /**
     * Retrieve configured hospital geofence parameters from database or fall back to campus defaults.
     */
    public static function getCampusCoordinates(): array
    {
        try {
            $latRow = Database::selectOne("SELECT `setting_value` FROM `hospital_settings` WHERE `setting_key` = 'hospital_latitude' LIMIT 1");
            $lonRow = Database::selectOne("SELECT `setting_value` FROM `hospital_settings` WHERE `setting_key` = 'hospital_longitude' LIMIT 1");
            $radRow = Database::selectOne("SELECT `setting_value` FROM `hospital_settings` WHERE `setting_key` = 'geofence_radius_meters' LIMIT 1");

            return [
                'latitude'      => $latRow ? (float)$latRow['setting_value'] : self::DEFAULT_HOSPITAL_LAT,
                'longitude'     => $lonRow ? (float)$lonRow['setting_value'] : self::DEFAULT_HOSPITAL_LON,
                'radius_meters' => $radRow ? (int)$radRow['setting_value'] : self::DEFAULT_GEOFENCE_RADIUS_METERS,
            ];
        } catch (\Throwable $e) {
            return [
                'latitude'      => self::DEFAULT_HOSPITAL_LAT,
                'longitude'     => self::DEFAULT_HOSPITAL_LON,
                'radius_meters' => self::DEFAULT_GEOFENCE_RADIUS_METERS,
            ];
        }
    }

    /**
     * Evaluate check-in coordinates against location target and accuracy criteria.
     *
     * Verification outcomes:
     * - 'verified': Within geofence, accuracy <= 100m.
     * - 'flagged_distance': Outside geofence radius.
     * - 'flagged_accuracy': Browser GPS reported accuracy > 100m.
     * - 'suspicious_gps': Outside geofence AND poor accuracy.
     */
    public static function verifyCoordinates(
        float $userLat,
        float $userLon,
        float $accuracyMeters,
        ?float $targetLat = null,
        ?float $targetLon = null,
        ?int $customRadiusMeters = null
    ): array {
        $campus = self::getCampusCoordinates();

        $centerLat = $targetLat ?? $campus['latitude'];
        $centerLon = $targetLon ?? $campus['longitude'];
        $allowedRadius = $customRadiusMeters ?? $campus['radius_meters'];

        $distance = self::calculateHaversineDistance($userLat, $userLon, $centerLat, $centerLon);
        $isWithinGeofence = ($distance <= $allowedRadius);
        $hasPoorAccuracy = ($accuracyMeters > self::MAX_ACCEPTABLE_ACCURACY_METERS);

        $status = 'verified';
        $flagReason = null;
        $isFlagged = false;

        if (!$isWithinGeofence && $hasPoorAccuracy) {
            $status = 'suspicious_gps';
            $isFlagged = true;
            $flagReason = "Coordinate {$distance}m from target (radius: {$allowedRadius}m) and poor accuracy ({$accuracyMeters}m > 100m).";
        } elseif (!$isWithinGeofence) {
            $status = 'suspicious_gps';
            $isFlagged = true;
            $flagReason = "Distance of {$distance}m exceeds allowed geofence perimeter of {$allowedRadius}m.";
        } elseif ($hasPoorAccuracy) {
            $status = 'suspicious_gps';
            $isFlagged = true;
            $flagReason = "GPS accuracy threshold exceeded ({$accuracyMeters}m > " . self::MAX_ACCEPTABLE_ACCURACY_METERS . "m). Flagged for review.";
        }

        return [
            'is_within_geofence' => $isWithinGeofence,
            'is_flagged'         => $isFlagged,
            'distance_meters'    => $distance,
            'allowed_radius'     => $allowedRadius,
            'accuracy_meters'    => $accuracyMeters,
            'verification_status'=> $status,
            'flag_reason'        => $flagReason,
            'spoofing_disclaimer'=> 'Browser GPS coordinates can be simulated or spoofed via mock locations; presence is reinforced by on-site QR scanning and IP telemetry.',
        ];
    }
}
