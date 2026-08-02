<?php

namespace App\Http\Controllers\Concerns;

use App\Support\AddressFormat;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait ValidatesTurkeyAddress
{
    /** @param  array<string, mixed>  $rules */
    protected function validateWithTurkeyAddress(Request $request, array $rules, array $messages = []): array
    {
        $validated = $request->validate(array_merge($rules, AddressFormat::validationRules()), $messages);

        if ($message = AddressFormat::assertDistrictMatchesCity($validated)) {
            throw ValidationException::withMessages(['districtId' => $message]);
        }

        return $validated;
    }
}
