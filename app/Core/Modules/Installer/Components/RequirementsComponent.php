<?php

namespace Flute\Core\Modules\Installer\Components;

use Flute\Core\Support\FluteComponent;
use Flute\Core\Modules\Installer\Services\SystemRequirements;

class RequirementsComponent extends FluteComponent
{
    /**
     * @var array
     */
    public $phpRequirements = [];

    /**
     * @var array
     */
    public $extensionRequirements = [];

    /**
     * @var array
     */
    public $directoryRequirements = [];
    
    /**
     * @var bool
     */
    public $allRequirementsMet = false;

    /**
     * Mount the component
     */
    public function mount()
    {
        $systemRequirements = app(SystemRequirements::class);
        
        $this->phpRequirements = $systemRequirements->checkPhpRequirements();
        $this->extensionRequirements = $systemRequirements->checkExtensionRequirements();
        $this->directoryRequirements = $systemRequirements->checkDirectoryRequirements();
        
        $this->allRequirementsMet = $systemRequirements->allRequirementsMet();
    }

    /**
     * Render the component
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('installer::yoyo.requirements', [
            'phpRequirements' => $this->phpRequirements,
            'extensionRequirements' => $this->extensionRequirements,
            'directoryRequirements' => $this->directoryRequirements,
            'allRequirementsMet' => $this->allRequirementsMet,
        ]);
    }
} 