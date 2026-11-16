<?php

namespace Tests\Unit;

use App\Support\ModulePlacement;
use App\Support\WardrobeDesign;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModulePlacementTest extends TestCase
{
    public function test_pack_and_suggestion_match_the_shared_millimetre_cases(): void
    {
        $rules = ModulePlacement::rules();
        $this->assertSame(1, $rules['version']);
        $this->assertSame(4, $rules['overlapMm']);

        $fit = ModulePlacement::packRun(3150, [900, 900, 900, 450]);
        $this->assertTrue($fit['fits']);
        $this->assertSame(0, $fit['leftoverMm']);
        $this->assertSame([0, 900, 1800, 2700], $fit['offsetsMm']);
        $this->assertFalse(ModulePlacement::packRun(3150, [900, 900, 900, 450, 450])['fits']);
        $this->assertSame(0, ModulePlacement::packRun(2700, [900, 900, 900])['leftoverMm']);
        $this->assertSame(250, ModulePlacement::packRun(2500, [900, 900, 450])['leftoverMm']);

        $exact = ModulePlacement::suggestRun(3150, [900, 450]);
        $this->assertSame([900, 900, 900, 450], $exact['widthsMm']);
        $this->assertTrue($exact['exact']);
        $this->assertSame([900, 900, 900], ModulePlacement::suggestRun(2700, [900, 450])['widthsMm']);
        $this->assertNull(ModulePlacement::suggestRun(400, [900, 450]));

        $before = ModulePlacement::freeIntervals(4000, [[0, 900], [900, 1800], [1800, 2250]]);
        $after = ModulePlacement::freeIntervals(4000, [[0, 900], [1800, 2250]]);
        $this->assertSame([1750], array_column($before, 'lengthMm'));
        $this->assertSame([900, 1750], array_column($after, 'lengthMm'));
    }

    public function test_rotated_and_corner_footprints_match_the_typescript_cases(): void
    {
        $room = $this->room();
        $first = $this->module('a');
        $this->assertSame('overlap', ModulePlacement::issue($this->module('b'), [$first], $room));
        $this->assertSame('ok', ModulePlacement::issue($this->module('b', ['xCm' => 145]), [$first], $room));
        $flat = $this->module('flat', ['xCm' => 200, 'yCm' => 200, 'widthCm' => 80, 'depthCm' => 10]);
        $upright = $this->module('up', ['xCm' => 200, 'yCm' => 200, 'widthCm' => 80, 'depthCm' => 10, 'rotation' => 90]);
        $this->assertSame('overlap', ModulePlacement::issue($upright, [$flat], $room));
        $diamond = $this->module('diamond', ['xCm' => 200, 'yCm' => 200, 'widthCm' => 100, 'depthCm' => 10, 'rotation' => 45]);
        $beside = $this->module('beside', ['xCm' => 230, 'yCm' => 200, 'widthCm' => 10, 'depthCm' => 10]);
        $this->assertSame('ok', ModulePlacement::issue($beside, [$diamond], $room));

        $neighbour = $this->module('near', ['xCm' => 80, 'yCm' => 40]);
        $corner = $this->module('corner', ['corner' => true, 'xCm' => 100, 'yCm' => 70, 'widthCm' => 90, 'depthCm' => 90]);
        $this->assertSame('corner', ModulePlacement::issue($corner, [$neighbour], $room));
        $pocket = $this->module('pocket', ['xCm' => 125, 'yCm' => 50, 'widthCm' => 30, 'depthCm' => 20]);
        $this->assertSame('ok', ModulePlacement::issue($pocket, [$corner], $room));
        $leftHit = $this->module('left-hit', ['xCm' => -20, 'yCm' => -20, 'widthCm' => 10, 'depthCm' => 10]);
        $this->assertSame('corner', ModulePlacement::issue($leftHit, [$this->module('left', ['corner' => true, 'cornerSide' => 'left', 'xCm' => 0, 'yCm' => 0, 'widthCm' => 90, 'depthCm' => 90])], []));
        $this->assertSame('ok', ModulePlacement::issue($leftHit, [$this->module('right', ['corner' => true, 'cornerSide' => 'right', 'xCm' => 0, 'yCm' => 0, 'widthCm' => 90, 'depthCm' => 90])], []));
        $this->assertSame('outside', ModulePlacement::issue($this->module('out', ['xCm' => 20, 'yCm' => 20]), [], $this->room(300)));
    }

    public function test_server_validation_rejects_a_new_overlap_and_still_opens_an_old_design(): void
    {
        $old = [
            'modules' => [[
                'id' => 'm1',
                'width' => 90,
                'planX' => 100,
                'planY' => 80,
                'planRotation' => 0,
            ], [
                'id' => 'm2',
                'width' => 90,
                'planX' => 100,
                'planY' => 80,
                'planRotation' => 0,
            ]],
        ];
        $this->assertSame($old, WardrobeDesign::validated($old));

        try {
            WardrobeDesign::validated($old + ['placementRules' => 1, 'depth' => 60]);
            $this->fail('Çakışan yeni kayıt kabul edildi.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('içine giriyor', $exception->validator->errors()->first());
        }
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed> */
    private function module(string $id, array $overrides = []): array
    {
        return array_merge([
            'id' => $id,
            'xCm' => 100,
            'yCm' => 80,
            'rotation' => 0,
            'widthCm' => 45,
            'depthCm' => 60,
            'corner' => false,
        ], $overrides);
    }

    /** @return list<array<string, mixed>> */
    private function room(int $size = 400): array
    {
        return [
            ['id' => 'south', 'start' => ['x' => 0, 'y' => 0], 'end' => ['x' => $size, 'y' => 0], 'thickness' => 12],
            ['id' => 'east', 'start' => ['x' => $size, 'y' => 0], 'end' => ['x' => $size, 'y' => $size], 'thickness' => 12],
            ['id' => 'north', 'start' => ['x' => $size, 'y' => $size], 'end' => ['x' => 0, 'y' => $size], 'thickness' => 12],
            ['id' => 'west', 'start' => ['x' => 0, 'y' => $size], 'end' => ['x' => 0, 'y' => 0], 'thickness' => 12],
        ];
    }
}
