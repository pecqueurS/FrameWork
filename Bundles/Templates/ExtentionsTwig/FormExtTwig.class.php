<?php

namespace Bundles\Templates\ExtentionsTwig;

use Bundles\Formulaires\Forms;

class FormExtTwig extends \Twig_Extension
{
    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            //new \Twig_SimpleFunction('form', 'twig_get_form', array('is_safe' => array('html'))),
            'form' => new \Twig_Function_Method($this, 'twig_get_form', array('is_safe' => array('html'))),
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'form';
    }

    public function twig_get_form($nameForm, $nameInput)
    {
        return Forms::$renderHTML[$nameForm][$nameInput];
    }

}

