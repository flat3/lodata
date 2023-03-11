<?php

declare(strict_types=1);

namespace Flat3\Lodata\Tests\Helpers;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

trait UseDriverStateAssertions
{
    protected $driverState = null;

    protected function keepDriverState()
    {
        $this->driverState = $this->captureDriverState();
    }

    protected function captureDriverState(): array
    {
        return [];
    }

    protected function clearDriverState(): void
    {
        $this->driverState = null;
    }

    protected function assertDriverStateUnchanged()
    {
        $this->assertEquals($this->driverState, $this->captureDriverState());
    }

    protected function assertDriverStateDiffSnapshot()
    {
        $driver = new StreamingJsonDriver;

        $this->driverState !== null && $this->assertDiffSnapshot(
            $driver->serialize($this->driverState),
            $driver->serialize($this->captureDriverState())
        );
    }

    protected function assertDiffSnapshot($left, $right)
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder(''));
        $result = $differ->diff($left, $right);

        if (!$result) {
            return;
        }

        $this->assertMatchesTextSnapshot($result);
    }
}