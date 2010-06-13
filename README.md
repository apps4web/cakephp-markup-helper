# CakePHP Markup Helper

MarkupHelper provides a fluent interface for building complex (X)HTML.

    echo $markup->div('class1')
      ->p->text('Hello world')->end
      ->end;

Check [Syntax Guide](http://wiki.github.com/tkyk/cakephp-markup-helper/syntax-guide) for more details about the syntax.

## Requirements

-  PHP 5.2 or later
-  CakePHP 1.2/1.3

## Installation

    cd plugins/
    git clone git://github.com/tkyk/cakephp-markup-helper markup

In your app_controller.php

    class AppController extends Controller {
       var $helpers = array('Markup.Markup');
    }
