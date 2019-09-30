<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    AbstractForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Forms;

use Devrun\Doctrine\DoctrineForms\EntityFormTrait;
use Devrun\InvalidArgumentException;
use Flame\Application\UI\Form;
use Nette\ArrayHash;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\CheckboxList;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextBase;
use Nette\Forms\IControl;
use Nette\Forms\Rendering\DefaultFormRenderer;
use Tracy\Debugger;

interface IDevrunForm
{
    /** @return DevrunForm */
    function create();
}


class DevrunForm extends Form implements IDevrunForm
{

    const IN_ARRAY = 'CmsModule\Forms\AdminForm::validateInArray';

    /** @var bool form in modal?, may be submit button hidden */
    protected $modalLayout = false;

    protected $formClass = ['form-horizontal'];

    protected $autoButtonClass = true;

    protected $labelControlClass = 'div class="col-sm-3 control-label"';

    protected $controlClass = 'div class=col-sm-9';

    use EntityFormTrait;


    public function create()
    {
        if (null === $this->entityMapper) throw new InvalidArgumentException("set 'inject(true)' for this form (DI settings...), we need auto entity mapper.");
    }


    /**
     * @return $this
     */
    public function bootstrap3Render()
    {
        /** @var $renderer DefaultFormRenderer */
        $renderer                                        = $this->getRenderer();
        $renderer->wrappers['error']['container']        = 'div role=alert class="alert alert-danger m-b-10"';
        $renderer->wrappers['error']['item']             = 'p';
        $renderer->wrappers['controls']['container']     = NULL;
        $renderer->wrappers['pair']['container']         = 'div class=form-group';
        $renderer->wrappers['pair']['.error']            = 'has-error';
        $renderer->wrappers['control']['container']      = $this->controlClass;
        $renderer->wrappers['label']['container']        = $this->labelControlClass;
        $renderer->wrappers['control']['description']    = 'span class=help-block';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';

        // make form and controls compatible with Twitter Bootstrap
        $this->getElementPrototype()->addAttributes(['class' => implode(' ', $this->formClass)]);

        static $usedPrimary = false;
        foreach ($this->getControls() as $control) {
            if ($this->autoButtonClass && $control instanceof Button) {
                $class = $usedPrimary ? 'btn btn-default' : 'btn btn-primary';
                if (!$usedPrimary && $this->modalLayout) $class = 'hidden';

                $control->getControlPrototype()->addClass($class);
                $usedPrimary = TRUE;

            } /** @var $control BaseControl */
            elseif ($control instanceof TextBase ||
                $control instanceof SelectBox ||
                $control instanceof MultiSelectBox
            ) {
                $control->getControlPrototype()->addClass('form-control');

            } elseif ($control instanceof Checkbox ||
                $control instanceof CheckboxList ||
                $control instanceof RadioList
            ) {
                $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
            }
        }

        return $this;
    }

    /**
     * @param array $class
     *
     * @return $this
     */
    public function addFormClass(array $class)
    {
        $this->formClass = array_merge($this->formClass, $class);
        return $this;
    }

    /**
     * @param array $class
     *
     * @return $this
     */
    public function setFormClass(array $class)
    {
        $this->formClass = $class;
        return $this;
    }

    /**
     * @param string $labelControlClass
     *
     * @return $this
     */
    public function setLabelControlClass($labelControlClass): DevrunForm
    {
        $this->labelControlClass = $labelControlClass;
        return $this;
    }

    /**
     * @param string $controlClass
     *
     * @return $this
     */
    public function setControlClass($controlClass): DevrunForm
    {
        $this->controlClass = $controlClass;
        return $this;
    }

    /**
     * @param boolean $autoButtonClass
     *
     * @return $this
     */
    public function setAutoButtonClass($autoButtonClass): DevrunForm
    {
        $this->autoButtonClass = $autoButtonClass;
        return $this;
    }


    /**
     * @param bool $modalLayout
     *
     * @return DevrunForm
     */
    public function setModalLayout(bool $modalLayout): DevrunForm
    {
        $this->modalLayout = $modalLayout;
        return $this;
    }


    public static function validateInArray(IControl $control, $val)
    {
        $return = in_array($val, $control->getValue());
        return $return;
    }


    /**
     * Nette fix getValues asArray not work correctly
     *
     * @param bool $asArray
     *
     * @return array|ArrayHash
     * @todo unresolved
     */
    public function _getValues($asArray = false)
    {
        $values = (array)parent::getValues($asArray);
        if (!isset($values['id']))
            $values['id'] = $this->getId();

        return $asArray ? $values : ArrayHash::from($values);
    }


}