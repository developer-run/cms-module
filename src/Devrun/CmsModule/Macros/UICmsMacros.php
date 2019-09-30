<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    UICmsMacro.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Macros;

use Devrun\CmsModule\Utils\Common;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;
use Nette\DI\Container;
use Nette\Security\User;
use Nette\Utils\Strings;
use Tracy\Debugger;

class UICmsMacros extends MacroSet
{

    /** @var Container */
    private $container;

    /** @var User */
    private $user;

    /** @var Compiler */
    private $compiler;

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }





    public static function install(Compiler $compiler)
    {
        $set = new static($compiler);
        $set->compiler = $compiler;

        $set->addMacro('id', NULL, NULL, array($set, 'macroId'));
        $set->addMacro('widgetArticle', array($set, 'macroWidgetArticle'));
//        $set->addMacro('editable', array($set, 'macroEditable'), array($set, 'macroEditableEnd'));

        $set->addMacro('editable', null, array($set, 'macroEditableEnd'), array($set, 'macroEditable'));
        $set->addMacro('edit', array($set, 'macroEditable'));

        $set->addMacro('image', [$set, 'tagImg'], NULL, [$set, 'attrImg']);




//        dump($set);
//        die();

    }





    /**
     * n:id="..."
     */
    public function macroId(MacroNode $node, PhpWriter $writer)
    {
        return $writer->write('if ($_l->tmp = array_filter(%node.array)) echo \' id="\' . %escape(implode(" ", array_unique($_l->tmp))) . \'"\'');
    }

    public function macroWidgetArticle(MacroNode $node, PhpWriter $writer)
    {

        $html = <<<EON
<div style=\"color: blue;\">Jak
<span>se češ</span>
máš
</div>

<div class=\"btn-group\">
  <button class=\"btn btn-default btn-xs dropdown-toggle\" type=\"button\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">
    Extra small button <span class=\"caret\"></span>
  </button>
  <ul class=\"dropdown-menu\">
    <li>polozka</li>
  </ul>
</div>
EON;

        $return = $writer->write('$_l->tmp = $_control->getComponent("article"); if ($_l->tmp instanceof Nette\Application\UI\IRenderable) $_l->tmp->redrawControl(NULL, FALSE); $_l->tmp->render(2);');
        $return .= $writer->write('echo "' . $html . '";');

        dump($return);

        return $return;

        return $writer->write('echo "' . $html . '";');

//        return $writer->write('if ($_l->tmp = array_filter(%node.array)) echo \' id="\' . %escape(implode(" ", array_unique($_l->tmp))) . \'"\'');
    }


    /**
     * {_$var |modifiers}
     */
    public function macroTranslate(MacroNode $node, PhpWriter $writer)
    {
        if ($node->closing) {
            return $writer->write('echo %modify($template->translate(ob_get_clean()))');

        } elseif ($node->isEmpty = ($node->args !== '')) {
//            if ($this->containsOnlyOneWord($node)) {
                return $writer->write('echo %modify($template->translate(%node.word))');

//            } else {
                return $writer->write('echo %modify($template->translate(%node.word, %node.args))');
//            }

        } else {
            return 'ob_start()';
        }

    }





    public function macroEditable(MacroNode $node, PhpWriter $writer)
    {
        // emulate only admin, we have not services dependency
        if (self::isAdminRequest()) {
            $content = $node->args;
            $word = $node->tokenizer->fetchWord();

            $option = '';
            if ($editorType = $node->tokenizer->fetchWord()) {
                $option = ' data-editor=\"' . $editorType . '\"';
            }

            $domain  = Strings::before($word, '.');
            $keyStr      = Strings::after($word, '.');

            return $writer->write('echo " contenteditable=\"true\" data-domain=\"' . $domain . '\" data-translate=\"' . $keyStr . '\"' . $option . '"');
        }
    }


    public function macroEditableEnd(MacroNode $node, PhpWriter $writer)
    {
        $word = $node->tokenizer->fetchWord();

        dump($word);


        return $writer->write('');

//        return $writer->write('if ($_l->tmp = array_filter(%node.array)) echo \' id="\' . %escape(implode(" ", array_unique($_l->tmp))) . \'"\'');
    }


    public function tagImg(MacroNode $node, PhpWriter $writer)
    {
        return $writer->write('$_img = $_imageStorage->fromIdentifier(%node.array); echo "<img src=\"" . $basePath . "/" . $_img->createLink() . "\">";');
    }




    public function attrImg(MacroNode $node, PhpWriter $writer)
    {
        return $writer->write('$_img = $_imageStorage->fromIdentifier(%node.array); echo \' src="\' . $basePath . "/" . $_img->createLink() . \'"\'');
    }






    /**
     * @return bool is request from admin page
     */
    public static function isAdminRequest()
    {
        return Common::isAdminRequest();
    }

}