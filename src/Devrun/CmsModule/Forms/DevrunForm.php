<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    AbstractForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Forms;

use Devrun\CmsModule\Forms\Rendering\Bs4FormRenderer;
use Devrun\DoctrineModule\DoctrineForms\EntityFormTrait;
use Devrun\InvalidArgumentException;
use Nette\Application\UI\Form;
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
use Nette\Utils\ArrayHash;

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

    protected $labelControlClass = 'div class="col-sm-2 col-form-label"';

    protected $controlClass = 'div class=col-sm-10';

    use EntityFormTrait;

    /** @var int */
    private $id;


    public function create()
    {
        if (null === $this->entityMapper) throw new InvalidArgumentException("set 'inject(true)' for this form (DI settings...), we need auto entity mapper.");
    }


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int)$id;
        return $this;
    }


    /**
     * Nette fix getValues asArray not work correctly
     *
     * @param bool $asArray
     * @return array|ArrayHash
     */
    public function getValues($asArray = false)
    {
        $values = (array)parent::getValues($asArray);
        if (!isset($values['id']))
            $values['id'] = $this->getId();

        return ArrayHash::from($values);
    }

    /**
     * @param array|\Traversable $values
     * @param bool                           $erase
     * @return \Nette\Forms\Container
     */
    public function setDefaults($values, $erase = false)
    {

        // Set form ID
        if (isset($values['id'])) {
            $this->setId($values['id']);
        } elseif (isset($values->id)) {
            $this->setId($values->id);
        }

        // Get object to string for values compatibility
        if (is_array($values) and count($values)) {
            $values = array_map(function ($value) {
                if (is_object($value) and (method_exists($value, '__toString'))) {
                    if (isset($value->id)) {
                        return (string)$value->id;
                    } else {
                        return (string)$value;
                    }

                }

                return $value;
            }, $values);
        }

        return parent::setDefaults($values, $erase);
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
     * @return $this
     */
    public function bootstrap4Render()
    {
        $this->setRenderer(new Bs4FormRenderer());
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


}