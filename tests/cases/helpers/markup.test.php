<?php
/**
 * Markup Helper Test file.
 *
 * Copyright (c) 2009 Takayuki Miwa <i@tkyk.name>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2009 Takayuki Miwa <i@tkyk.name>
 * @link          http://wp.serpere.info/
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::import('View', 'View');
App::import('Helper', 'Markup.Markup');

Mock::generate('Helper');
Mock::generate('View');

function _s($obj) {
    return strval($obj);
}

class MarkupTestCase extends CakeTestCase
{
    var $v;
    var $h;

    function startTest() {
       $this->h = new MarkupHelper();

       $c = new Controller;
       $this->v = new View($c, true);

       $this->h->beforeRender();
    }

    function endTest() {
        ClassRegistry::flush();
    }

    function testStartTag() {
       $h = $this->h;

       $this->assertIdentical($h, $h->startTag('div'));

       $this->assertEqual('<div>', _s($h));
       $this->assertEqual('<div class="css-class">',
                          _s($h->startTag('div', 'css-class')));

       $this->assertEqual('<div title="foo" id="bar">',
                          _s($h->startTag('div', array('title' => 'foo',
                                                    'id'    => 'bar'))));
       $this->assertEqual('<div id="&lt;&gt;">',
                          _s($h->startTag('div', array('id' => '<>'))));

       $this->assertEqual('<div>foo</div>',
                          _s($h->startTag('div', null, 'foo')));

       $this->assertEqual('<div title="sample">foo</div>',
                          _s($h->startTag('div', array('title' => 'sample'), 'foo')));

       $this->assertEqual('<div title="sample">&lt;foo&gt;</div>',
                          _s($h->startTag('div', array('title' => 'sample'), '<foo>')));

       $this->assertEqual('<div title="sample"><foo></div>',
                          _s($h->startTag('div', array('title' => 'sample'), '<foo>', false)));

    }

    function testStartTagChain() {
       $h = $this->h;

       $this->assertEqual('<div class="foo"><div id="bar"><p>baz</p>',
                          _s($h->startTag('div', 'foo')
                             ->startTag('div', array('id' => 'bar'))
                             ->startTag('p', null, 'baz')));

       $this->assertEqual('<div class="foo"><p class="first">bar</p><p class="second">baz</p>',
                          _s($h->startTag('div', 'foo')
                             ->startTag('p', 'first', 'bar')
                             ->startTag('p', 'second', 'baz')));

    }

    function testEndTag() {
       $h = $this->h;

       $this->assertIdentical($h, $h->endTag());
       $this->assertEqual('', _s($h->endTag()));
       $this->assertEqual('', _s($h->endTag()->endTag()->endTag()));

       $this->assertEqual('<div></div>',
                          _s($h->startTag('div')->endTag()));

       $this->assertEqual('<div>foo</div>',
                          _s($h->startTag('div', null, 'foo')->endTag()));

       $this->assertEqual('<p><strong><i></i></strong></p>',
                          _s($h->startTag('p')
                             ->startTag('strong')
                             ->startTag('i')
                             ->endTag()
                             ->endTag()
                             ->endTag()));
    }

    function testEndTagWithExplicitTagName() {
       $h = $this->h;

       $this->assertEqual('<div></div>',
                          _s($h->startTag('div')->endTag('div')));

       $this->assertEqual('<p><strong><i></i></strong></p>',
                          _s($h->startTag('p')
                             ->startTag('strong')
                             ->startTag('i')
                             ->endTag('i')
                             ->endTag('strong')
                             ->endTag('p')));

       $this->assertEqual('<p><strong><i>closeAll</i></strong></p>',
                          _s($h->startTag('p')
                             ->startTag('strong')
                             ->startTag('i', null, 'closeAll')
                             ->endTag('p')));

       $this->assertNoErrors();
    }

    function testEndTagError() {
       $h = $this->h;

       $this->assertEqual('', _s($h->endTag('div')));
       $this->assertErrorPattern('/unopened tag: div/');

       $this->assertEqual('', _s($h->endTag('div')->endTag('p')));
       $this->assertErrorPattern('/unopened tag: div/');
       $this->assertErrorPattern('/unopened tag: p/');

       $this->assertEqual('<div>foo</div>',
                          _s($h->startTag('div', null, 'foo')->endTag('div')));
       $this->assertErrorPattern('/unopened tag: div/');

       $this->assertEqual('<p><strong><i></i></strong></p>',
                          _s($h->startTag('p')
                             ->startTag('strong')
                             ->startTag('i')
                             ->endTag()
                             ->endTag('div')
                             ->endTag('p')));
       $this->assertErrorPattern('/unopened tag: div/');

       $this->assertEqual('<p><strong><i>',
                          _s($h->startTag('p')
                             ->startTag('strong')
                             ->startTag('i')
                             ->endTag('span')));
       $this->assertErrorPattern('/unopened tag: span/');
    }

    function testEndAllTags() {
       $h = $this->h;

       $this->assertIdentical($h, $h->endAllTags());

       $this->assertEqual('', _s($h->endAllTags()));
       $this->assertEqual('<p></p>', _s($h->startTag('p')->endAllTags()));
       $this->assertEqual('<p><strong><i></i></strong></p>',
                          _s($h->startTag('p')
                             ->startTag('strong')
                             ->startTag('i')
                             ->endAllTags()));

       $this->assertEqual('<p><strong><i></i></strong></p>',
                          _s($h->startTag('p')
                             ->startTag('strong')
                             ->startTag('i')
                             ->endAllTags()
                             ->endTag()));
    }

    function testClear() {
       $h = $this->h;

       $h->startTag('div')->startTag('p');
       $h->clear();
       $this->assertEqual('', _s($h));
       $this->assertEqual('', _s($h->endTag()));
    }

    function testNewline() {
       $h = $this->h;

       $nl = "\n";
       $this->assertIdentical($h, $h->newline());

       $this->assertEqual($nl, _s($h));
       $this->assertEqual('<div class="foo">'.$nl,
                          _s($h->startTag('div', 'foo')->newline()));
       $this->assertEqual('<div class="foo">bar</div>'.$nl,
                          _s($h->startTag('div', 'foo', 'bar')->newline()));
       $this->assertEqual('<div class="foo"></div>'.$nl,
                          _s($h->startTag('div', 'foo')->endTag()->newline()));
    }

    function testEmptyElements() {
       $h = $this->h;

       $this->assertEqual("<br />", _s($h->startTag('br')));
       $this->assertEqual("", _s($h->endTag()));

       $this->assertEqual('<p><br /><strong><br /></strong></p>',
                          _s($h->startTag('p')
                             ->startTag('br')
                             ->startTag('strong')
                             ->startTag('br')
                             ->endTag('p')));

       $this->assertEqual('<img src="path.jpg" alt="alt text..." />',
                          _s($h->startTag('img', array('src' => 'path.jpg',
                                                       'alt' => 'alt text...'),
                                          'NEVER USED', false)));

       $this->assertEqual('<hr class="line" />',
                          _s($h->startTag('hr', 'line', 'NEVER USED', false)));
    }

    function testText() {
       $h = $this->h;

       $this->assertIdentical($h, $h->text("foo"));
       $this->assertEqual("foo", _s($h));
       $this->assertEqual('&lt;foo&gt;&quot;', _s($h->text('<foo>"')));
       $this->assertEqual('<p>foo</p>',
                          _s($h->startTag('p')->text('foo')->endTag()));
       $this->assertEqual('<p>foobarzoo</p>',
                          _s($h->startTag('p')->text('foo', 'bar', 'zoo')->endTag()));
       $this->assertEqual('<p>foo<strong>bar</strong>baz</p>',
                          _s($h->startTag('p')
                             ->text('foo')
                             ->startTag('strong')
                             ->text('bar')
                             ->endTag()
                             ->text('baz')
                             ->endTag()));
    }

    function testHtml() {
       $h = $this->h;

       $this->assertIdentical($h, $h->html("foo"));
       $this->assertEqual("foo", _s($h));
       $this->assertEqual('<foo>"', _s($h->html('<foo>"')));
       $this->assertEqual('<p>foo</p>',
                          _s($h->startTag('p')->html('foo')->endTag()));
       $this->assertEqual('<p><foo><bar><zoo></p>',
                          _s($h->startTag('p')->html('<foo>', '<bar>', '<zoo>')->endTag()));
       $this->assertEqual('<p>foo<strong>bar</strong>baz</p>',
                          _s($h->startTag('p')
                             ->html('foo')
                             ->html('<strong>bar</strong>')
                             ->text('baz')
                             ->endTag()));
    }

    function testPushAndPopContext() {
       $h = $this->h;

       $h->startTag('div')->startTag('p');

       $h->pushNewContext();
       $this->assertEqual('', _s($h));
       $this->assertEqual('', _s($h->endTag()->endTag()));
       $this->assertEqual('<dl><dt></dt>',
                          _s($h->startTag('dl')->startTag('dt')->endTag()));
       $h->pushNewContext();
       $this->assertEqual('', _s($h));
       $this->assertEqual('', _s($h->endTag()));
       $this->assertEqual('<ul><li><span class="foo">',
                          _s($h->startTag('ul')->startTag('li')->startTag('span', 'foo')));
       $this->assertEqual('aaa</span></li></ul>',
                          _s($h->text('aaa')->endAllTags()));
       $h->popContext();
       $this->assertEqual('</dl>',
                          _s($h->endAllTags()));
       $h->popContext();
       $this->assertEqual('<div><p></p></div>', _s($h->endAllTags()));

       $h->startTag('ol');
       $h->popContext(); //No context is on the stack.
       $this->assertEqual('<ol></ol>', _s($h->endTag()));
    }

    function testPopContextReturnsString() {
       $h = $this->h;

       $h->startTag('div')->startTag('p');

       $h->pushNewContext();
       $this->assertEqual('', _s($h));
       $this->assertEqual('', _s($h->endTag()->endTag()));
       $this->assertEqual('<dl><dt></dt>',
                          _s($h->startTag('dl')->startTag('dt')->endTag()));
       $h->pushNewContext();

       $this->assertEqual('', _s($h));
       $this->assertEqual('', _s($h->endTag()));

       $h->startTag('ul')->startTag('li')->startTag('span', 'foo')
           ->text('aaa')->endAllTags();

       $this->assertEqual('<ul><li><span class="foo">aaa</span></li></ul>',
                          $h->popContext());

       $this->assertEqual('</dl>',
                          $h->endAllTags()->popContext());

       $this->assertEqual('<div><p></p></div>', _s($h->endAllTags()));

       $h->startTag('ol');
       $this->assertEqual('', $h->popContext()); //No context is on the stack.
       $this->assertEqual('<ol></ol>', _s($h->endTag()));
    }

    function testPopContextReturnsStringHandlySyntax() {
       $this->h->div('outer');

       $this->assertEqual('<div class="inner"><p>foo</p></div>',
                          $this->h->pushNewContext
                          ->div('inner')->p->text('foo')->endAllTags->popContext);

       $this->assertEqual('<div class="outer"></div>',
                          _s($this->h->endAllTags));
    }

    function testShortCutMethods1() {
       $h = $this->h;

       $this->assertEqual('<div class="foo"><p title="aaaaaa"><strong>bar</strong></p></div>',
                          _s($h->div("foo")
                             ->p(array('title' => "aaaaaa"))
                             ->strong
                             ->text('bar')
                             ->end
                             ->end
                             ->endAllTags));

       $this->assertEqual('<table><tr><td>bar</td></tr></table>',
                          _s($h->table
                             ->tr->td(null, 'bar')->end->end));
    }

    function testShortCutMethods2() {
       $h = $this->h;

       $this->assertEqual('<div class="foo"><p title="aaaaaa"><strong>bar</strong></p></div>',
                          _s($h->div("foo")
                             ->p(array('title' => "aaaaaa"))
                             ->strong
                             ->text('bar')
                             ->endstrong
                             ->endp
                             ->enddiv));

       $this->assertEqual("<table><tr><td>bar</td>\n</tr></table>",
                          _s($h->table
                             ->tr->td(null, 'bar')->nl->endtable));
    }

    function testShortCutMethodsFlipArgs() {
       $h = $this->h;

       $this->assertEqual('<div class="foo">&lt;aaa&gt;</div>', _s($h->div_("<aaa>", "foo")));
       $this->assertEqual('<div>&lt;aaa&gt;</div>', _s($h->div_("<aaa>")));
       $this->assertEqual('<div>&lt;aaa&gt;</div>', _s($h->div_("<aaa>", null)));
       $this->assertEqual('<div><aaa></div>', _s($h->div_("<aaa>", null, false)));

       $this->assertEqual('<div>', _s($h->div_()));
       $this->assertEqual('<div>', _s($h->div_(null)));
       $this->assertEqual('<div class="foo">', _s($h->div_(null, "foo")));
    }

    function testAliasMethod() {
       $h = $this->h;

       $h->aliasMethod('newline2', 'newline');
       $h->aliasMethod('div2', 'div');

       $this->assertEqual("<div>\n</div>",
                          _s($this->h->div2->newline2->end));
    }

}

class MarkupHelper_OtherHelpersTestCase extends CakeTestCase
{
    var $h;

    function startTest() {
       $this->h = new MarkupHelper();
    }

    function endTest() {
        ClassRegistry::flush();
    }

    function _createView() {
        $c = new Controller;
        return new View($c, true);
    }

    /**
     * - Add HelperName to $helpers
     * - Add (prefix => HelperName) to $prefix2Helper 
     */
    function testUseHelper() {
       $h = $this->h;

       $helperCount = count($h->helpers);
       $prefixCount = count($h->prefix2Helper);


       $h->useHelper(array('name' => 'Foo'));
       $this->assertTrue(in_array('Foo', $h->helpers));
       $this->assertEqual($helperCount + 1,
                          ($helperCount = count($h->helpers)));
       $this->assertEqual($prefixCount,
                          ($prefixCount = count($h->prefix2Helper)));


       $h->useHelper(array('name' => 'Foo',
                           'prefix' => 'fff'));
       $this->assertTrue(in_array('Foo', $h->helpers));
       $this->assertEqual('Foo', $h->prefix2Helper['fff']);
       $this->assertEqual($helperCount,
                          ($helperCount = count($h->helpers)));
       $this->assertEqual($prefixCount + 1,
                          ($prefixCount = count($h->prefix2Helper)));


       $h->useHelper('Bar');
       $this->assertTrue(in_array('Bar', $h->helpers));
       $this->assertEqual($helperCount + 1,
                          ($helperCount = count($h->helpers)));
       $this->assertEqual($prefixCount,
                          ($prefixCount = count($h->prefix2Helper)));
    }

    function assertHelperPrefixMatch($p, $x, $match1, $match2) {
        $this->assertTrue(preg_match($p, $x, $m));
        $this->assertEqual($match1, $m[1]);
        $this->assertEqual($match2, $m[2]);
    }

    function testBuildHelperRegex() {
        $h = $this->h;

        $regex = $h->buildHelperRegex(array('Html', 'Form', 'FooBar'));

        $this->assertHelperPrefixMatch($regex, 'Html_link', 'Html', 'link');
        $this->assertHelperPrefixMatch($regex, 'Form_create', 'Form', 'create');
        $this->assertHelperPrefixMatch($regex, 'FooBar_xxx_yyy', 'FooBar', 'xxx_yyy');

        $this->assertNoPattern($regex, 'html_xxxx');
        $this->assertNoPattern($regex, 'Html');
        $this->assertNoPattern($regex, 'Form_');
        $this->assertNoPattern($regex, 'Unknown_');
    }

    function testBuildHelperRegex_customPrefixes() {
        $h = $this->h;

        $regex = $h->buildHelperRegex(array('FooBar'));

        $this->assertHelperPrefixMatch($regex, 'FooBar_xxx_yyy', 'FooBar', 'xxx_yyy');
        $this->assertNoPattern($regex, 't_xyz');
        $this->assertNoPattern($regex, 'fb_xxx_yyy');

        $regex = $h->buildHelperRegex(array('Test', 'FooBar'),
                                      array('t', 'fb'));
        $this->assertHelperPrefixMatch($regex, 'FooBar_xxx_yyy', 'FooBar', 'xxx_yyy');
        $this->assertHelperPrefixMatch($regex, 't_xyz', 't', 'xyz');
        $this->assertHelperPrefixMatch($regex, 'fb_xxx_yyy', 'fb', 'xxx_yyy');
        $this->assertNoPattern($regex, 'x_lkjkfdsa');
    }

    function testCallHelperMethod() {
        $h = $this->h;

        $v = $this->_createView();
        $v->loaded['html'] = new MockHelper();
        $v->loaded['fooBar'] = new MockHelper();
        $h->useHelper(array('name' => 'FooBar', 'prefix' => 'fb'));

        // execute beforeRender callback
        $h->beforeRender();

        $args = array('label', '/path');

        $dispatch = array('link', $args);
        $v->loaded['html']->expectOnce('dispatchMethod', $dispatch);
        $v->loaded['html']->setReturnValue('dispatchMethod', '<a>', $dispatch);

        $dispatch = array('test_method', $args);
        $v->loaded['fooBar']->expectOnce('dispatchMethod', $dispatch);
        $v->loaded['fooBar']->setReturnValue('dispatchMethod', 'test return', $dispatch);

        $this->assertEqual('<a>',
                           $h->callHelperMethod('Html', 'link', $args));
        $this->assertEqual('test return',
                           $h->callHelperMethod('fb', 'test_method', $args));
    }

    function test__CallHelperMethods() {
        $h = $this->h;

        $v = $this->_createView();
        $v->loaded['html'] = new MockHelper();
        $v->loaded['fooBar'] = new MockHelper();
        $h->useHelper(array('name' => 'FooBar', 'prefix' => 'fb'));

        // execute beforeRender callback
        $h->beforeRender();

        $args = array('label', '/path');

        $dispatch = array('link', $args);
        $v->loaded['html']->expectOnce('dispatchMethod', $dispatch);
        $v->loaded['html']->setReturnValue('dispatchMethod', '<a>', $dispatch);

        $dispatch = array('test_method', $args);
        $v->loaded['fooBar']->expectOnce('dispatchMethod', $dispatch);
        $v->loaded['fooBar']->setReturnValue('dispatchMethod', '<test /><return />', $dispatch);

        $this->assertIdentical($h, $h->Html_link($args[0], $args[1]));
        $this->assertEqual('<a>', _s($h));

        $this->assertIdentical($h, $h->fb_test_method($args[0], $args[1]));
        $this->assertEqual('<test /><return />', _s($h));

        $this->assertIdentical($h, $h->Unknown_test_method("a", "b"));
        $this->assertEqual('<Unknown_test_method class="a">b</Unknown_test_method>', _s($h));
    }

    function testConstructor() {
       $h = new MarkupHelper();

       $this->assertEqual(2, count($h->helpers));
       $this->assertTrue(in_array('Html', $h->helpers));
       $this->assertTrue(in_array('Form', $h->helpers));
       $this->assertEqual('Html', $h->prefix2Helper['h']);
       $this->assertEqual('Form', $h->prefix2Helper['f']);

       $h2 = new MarkupHelper(array('helpers' => array('Foo',
                                                       'Bar' => array('prefix' => 'b'),
                                                       'Zoo' => 'z',
                                                       array('name' => 'Baz', 'prefix' => 'h'))));

       $this->assertEqual(6, count($h2->helpers));
       foreach(array('Html', 'Form', 'Foo', 'Bar', 'Zoo', 'Baz') as $a) {
           $this->assertTrue(in_array($a, $h2->helpers));
       }
       $this->assertEqual('Baz', $h2->prefix2Helper['h']);
       $this->assertEqual('Form', $h2->prefix2Helper['f']);
       $this->assertEqual('Bar', $h2->prefix2Helper['b']);
       $this->assertEqual('Zoo', $h2->prefix2Helper['z']);

    }

    function testRenderElement() {
        $h = $this->h;

        $v = new MockView();
        ClassRegistry::addObject('view', $v);
        
        // execute beforeRender
        $h->beforeRender();

        $v->expectCallCount('dispatchMethod', 2);
        $v->expectAt(0, 'dispatchMethod', array('element', array('element1')));
        $v->expectAt(1, 'dispatchMethod', array('element', array('element2', array('var' => true))));

        $h->renderElement('element1');
        $h->renderElement('element2', array('var' => true));
    }

    function testRenderElement_context() {
        $h = $this->h;
        $className = get_class($this).uniqid()."TestView";

        $code = 'class '. $className .' extends View {
            var $h;
            function __construct($h){ $this->h = $h; }
            function element($e) {
                return strval($this->h->p->text($e)->endAllTags);
            }
        }';
        eval($code);

        $v = new $className($h);
        ClassRegistry::addObject('view', $v);
        $h->beforeRender();

        $this->assertEqual('<div>', _s($h->div));
        $this->assertEqual('<p>element1</p>',
                           _s($h->renderElement('element1')));
        $this->assertEqual('</div>', _s($h->end));

        $this->assertEqual('<div class="a"><p>element2</p></div>',
                           _s($h->div("a")->renderElement('element2')->enddiv));
    }


    function testBeforeRender_noRegister() {
        $h = $this->h;
        $h->useHelper(array('name' => 'FooBar', 'prefix' => 'fb'));

        // EmailComponent does not register the view to the ClassRegistry!
        $view = ClassRegistry::getObject('view');
        $this->assertTrue(empty($view));

        // These assignments are done by view
        $h->Html = new MockHelper();
        $h->FooBar = new MockHelper();

        $this->assertEqual(array('Html', 'Form', 'FooBar'),
                           $h->helpers);

        // execute beforeRender callback
        $h->beforeRender();

        $args = array('label', '/path');

        $dispatch = array('link', $args);
        $h->Html->expectOnce('dispatchMethod', $dispatch);
        $h->Html->setReturnValue('dispatchMethod', '<a>', $dispatch);

        $dispatch = array('test_method', $args);
        $h->FooBar->expectOnce('dispatchMethod', $dispatch);
        $h->FooBar->setReturnValue('dispatchMethod', '<test /><return />', $dispatch);

        $this->assertIdentical($h, $h->Html_link($args[0], $args[1]));
        $this->assertEqual('<a>', _s($h));

        $this->assertIdentical($h, $h->fb_test_method($args[0], $args[1]));
        $this->assertEqual('<test /><return />', _s($h));

        $this->assertIdentical($h, $h->Unknown_test_method("a", "b"));
        $this->assertEqual('<Unknown_test_method class="a">b</Unknown_test_method>', _s($h));        
    }

}
