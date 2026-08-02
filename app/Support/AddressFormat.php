<?php

namespace App\Support;

use App\Models\TurkeyCity;
use App\Models\TurkeyDistrict;

class AddressFormat
{
    /** @return array{cityId: ?int, districtId: ?int} */
    public static function resolveIdsFromText(?string $address): array
    {
        if (! $address || ! trim($address)) {
            return ['cityId' => null, 'districtId' => null];
        }

        $normalized = mb_strtolower($address, 'UTF-8');
        $matchedCity = null;

        foreach (TurkeyCity::query()->orderByRaw('CHAR_LENGTH(name) DESC')->get(['id', 'name']) as $city) {
            $cityName = mb_strtolower($city->name, 'UTF-8');
            if ($cityName !== '' && str_contains($normalized, $cityName)) {
                $matchedCity = $city;
                break;
            }
        }

        if (! $matchedCity) {
            return ['cityId' => null, 'districtId' => null];
        }

        $matchedDistrict = null;
        foreach (TurkeyDistrict::query()
            ->where('cityId', $matchedCity->id)
            ->orderByRaw('CHAR_LENGTH(name) DESC')
            ->get(['id', 'name']) as $district) {
            $districtName = mb_strtolower($district->name, 'UTF-8');
            if ($districtName !== '' && str_contains($normalized, $districtName)) {
                $matchedDistrict = $district;
                break;
            }
        }

        return [
            'cityId' => (int) $matchedCity->id,
            'districtId' => $matchedDistrict ? (int) $matchedDistrict->id : null,
        ];
    }

    /** @return array{cityId: mixed, districtId: mixed} */
    public static function fieldIds(object $entity): array
    {
        $cityId = old('cityId', $entity->cityId ?? null);
        $districtId = old('districtId', $entity->districtId ?? null);

        if (! $cityId && ! empty($entity->address)) {
            $resolved = self::resolveIdsFromText((string) $entity->address);
            $cityId = $resolved['cityId'] ?? null;
            $districtId = $districtId ?: ($resolved['districtId'] ?? null);
        }

        return compact('cityId', 'districtId');
    }

    public static function validationRules(): array
    {
        return [
            'address' => 'nullable|string|max:2000',
            'cityId' => 'nullable|integer|exists:turkey_cities,id',
            'districtId' => 'nullable|integer|exists:turkey_districts,id',
        ];
    }

    /** @param  array<string, mixed>  $validated */
    public static function assertDistrictMatchesCity(array &$validated): ?string
    {
        $cityId = $validated['cityId'] ?? null;
        $districtId = $validated['districtId'] ?? null;

        if (!$districtId) {
            return null;
        }

        if (!$cityId) {
            return 'İlçe seçmek için önce il seçmelisiniz.';
        }

        $matches = TurkeyDistrict::query()
            ->where('id', $districtId)
            ->where('cityId', $cityId)
            ->exists();

        return $matches ? null : 'Seçilen ilçe, seçilen ile ait değil.';
    }

    public static function format(object $entity): string
    {
        if (($entity->cityId ?? null) && method_exists($entity, 'relationLoaded') && ! $entity->relationLoaded('city')) {
            $entity->load(['city', 'district']);
        }

        $street = trim((string) ($entity->address ?? ''));
        $district = trim((string) ($entity->district?->name ?? ''));
        $city = trim((string) ($entity->city?->name ?? ''));
        $location = trim($district . ($district && $city ? ' / ' : '') . $city);

        return trim($street . ($street && $location ? "\n" : '') . $location);
    }

    public static function locationLine(object $entity): string
    {
        $district = trim((string) ($entity->district?->name ?? ''));
        $city = trim((string) ($entity->city?->name ?? ''));

        return trim($district . ($district && $city ? ' / ' : '') . $city);
    }
}
