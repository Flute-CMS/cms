<?php

namespace Flute\Core\Installer\Steps;

use InvalidArgumentException;

class StepFactory
{
    /**
     * Creates an instance of the installation step based on the provided step ID.
     *
     * @param int $stepId The ID of the installation step.
     * @return AbstractStep The instance of the corresponding installation step.
     * @throws InvalidArgumentException If the step ID does not correspond to a valid step.
     */
    public static function create(int $stepId): AbstractStep
    {
        switch ($stepId) {
            case 1:
                return new LangStep();
            case 2:
                return new ReqsStep();
            case 3:
                return new DatabaseStep();
            case 4:
                // return a step class for step 4
            case 5:
                return new AdminStep();
            case 6:
                return new TipsStep();
            case 7:
                return new ShareStep();
            default:
                throw new InvalidArgumentException("Invalid step ID: {$stepId}");
        }
    }
}
