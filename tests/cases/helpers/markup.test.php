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

App::import('Helper', 'Markup.Markup');

function _s($obj) {
    return strval($obj);
}

class __HelperForTeset extends AppHelper
{
    function method1(){ return join("1", func_get_args()); }
    function x_x_method2(){ return join("2", func_get_args()); }
    function link(){ return join("_link_", func_get_args()); }
    function create(){ return join("_create_", func_get_args()); }
}
Mock::generatePartial('__HelperForTeset', 'Mock__HelperForTeset',
                      array('method1', 'x_x_method2', 'link', 'create'));


class MarkupTestCase extends CakeTestCase
{
    var $h;

    function startTest() {
       $this->h = new MarkupHelper();
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

    function testUseAndHelperVariables() {
       $h = $this->h;

       $this->assertTrue(in_array('Html', $h->helpers));
       $this->assertTrue(in_array('Form', $h->helpers));
       $c = count($h->helpers);

       $h->useHelper(array('name' => 'Foo'));
       $this->assertTrue(in_array('Foo', $h->helpers));
       $c2 = count($h->helpers);
       $this->assertEqual($c + 1, $c2);

       $h->useHelper(array('name' => 'Foo',
                           'prefix' => 'fff'));
       $this->assertTrue(in_array('Foo', $h->helpers));
       $c3 = count($h->helpers);
       $this->assertEqual($c2, $c3);

       $h->useHelper('Bar');
       $this->assertTrue(in_array('Bar', $h->helpers));
       $c4 = count($h->helpers);
       $this->assertEqual($c2 + 1, $c4);
    }

    function testUseAndFindHelperMethod() {
       $h = $this->h;

       $this->assertFalse($h->findHelperMethod('Foo_method1'));

       $h->useHelper(array('name' => 'Foo'));

       $this->assertEqual(array('Foo', 'method1'),
                          $h->findHelperMethod('Foo_method1'));
       $this->assertFalse($h->findHelperMethod('Foo'));
       $this->assertFalse($h->findHelperMethod('Foo_'));
       $this->assertFalse($h->findHelperMethod('foo_method1'));
       $this->assertFalse($h->findHelperMethod('x_method1'));

       $h->useHelper(array('name' => 'Foo',
                           'prefix' => 'x'));

       $this->assertEqual(array('Foo', 'method1'),
                          $h->findHelperMethod('x_method1'));
       $this->assertEqual(array('Foo', 'x_x_method2'),
                          $h->findHelperMethod('x_x_x_method2'));
       $this->assertEqual(array('Foo', 'method1'),
                          $h->findHelperMethod('Foo_method1'));
       $this->assertFalse($h->findHelperMethod('x'));
       $this->assertFalse($h->findHelperMethod('x_'));

       $h->useHelper(array('name' => 'Bar',
                           'prefix' => 'x'));

       $this->assertEqual(array('Bar', 'method1'),
                          $h->findHelperMethod('x_method1'));
       $this->assertEqual(array('Bar', 'method1'),
                          $h->findHelperMethod('Bar_method1'));
       $this->assertEqual(array('Foo', 'method1'),
                          $h->findHelperMethod('Foo_method1'));
    }

    function testUseAndCallHelperMethod() {
       $h = $this->h;

       $h->useHelper(array('name' => 'Foo',
                        'prefix' => 'x'));
       $h->useHelper(array('name' => 'Bar'));

       $h->Foo =& new Mock__HelperForTeset(); //this will be done by the View
       $h->Bar =& new Mock__HelperForTeset(); //this will be done by the View

       $h->Foo->expectOnce('method1', array(1, 2, 3));
       $h->Foo->setReturnValue('method1', 'one, two, three at Foo::method1');
       $h->Foo->expectOnce('link', array('call_link'));
       $h->Foo->setReturnValue('link', '<a>');
       $h->Foo->expectOnce('x_x_method2', array('xcall'));
       $h->Foo->setReturnValue('x_x_method2', '<x>');

       $h->Bar->expectOnce('method1', array('a', 'b'));
       $h->Bar->setReturnValue('method1', '<a><b>');

       $ret = $h->Foo_method1(1, 2, 3);
       $this->assertIdentical($h, $ret);
       $this->assertEqual('one, two, three at Foo::method1',
                          _s($h));
       $this->assertEqual('<a>',
                          _s($h->x_link("call_link")));
       $this->assertEqual('<x>',
                          _s($h->x_x_x_method2("xcall")));
       $this->assertEqual('<a><b>',
                          _s($h->Bar_method1('a', 'b')));
    }

    function testConstructor() {
       $h = new MarkupHelper();

       $this->assertEqual(2, count($h->helpers));
       $this->assertEqual(array('Html', 'method1'),
                          $h->findHelperMethod('Html_method1'));
       $this->assertEqual(array('Html', 'method1'),
                          $h->findHelperMethod('h_method1'));
       $this->assertEqual(array('Form', 'method1'),
                          $h->findHelperMethod('Form_method1'));
       $this->assertEqual(array('Form', 'method1'),
                          $h->findHelperMethod('f_method1'));


       $h2 = new MarkupHelper(array('helpers' => array('Foo',
                                                       'Bar' => array('prefix' => 'b'),
                                                       'Zoo' => 'z',
                                                       array('name' => 'Baz', 'prefix' => 'h'))));
       
       $this->assertEqual(6, count($h2->helpers));
       $this->assertEqual(array('Html', 'method1'),
                          $h2->findHelperMethod('Html_method1'));
       $this->assertEqual(array('Baz', 'method1'),
                          $h2->findHelperMethod('h_method1'));
       $this->assertEqual(array('Zoo', 'method1'),
                          $h2->findHelperMethod('z_method1'));
       $this->assertEqual(array('Form', 'method1'),
                          $h2->findHelperMethod('Form_method1'));
       $this->assertEqual(array('Foo', 'method1'),
                          $h2->findHelperMethod('Foo_method1'));
       $this->assertEqual(array('Bar', 'method1'),
                          $h2->findHelperMethod('Bar_method1'));
       $this->assertEqual(array('Bar', 'method1'),
                          $h2->findHelperMethod('b_method1'));
    }

    function testAliasMethod() {
       $h = $this->h;

       $h->aliasMethod('newline2', 'newline');
       $h->aliasMethod('div2', 'div');

       $this->assertEqual("<div>\n</div>",
                          _s($this->h->div2->newline2->end));
    }

    function testImportHelperMethods() {
       $h = $this->h;

       $h->useHelper(array('name' => 'Foo',
                           'prefix' => 'x'));
       $h->useHelper(array('name' => 'Bar'));

       $h->Foo =& new Mock__HelperForTeset(); //this will be done by the View
       $h->Bar =& new Mock__HelperForTeset(); //this will be done by the View

       $h->Foo->expectOnce('link', array('call_link'));
       $h->Foo->setReturnValue('link', '<a>');
       $h->Foo->expectOnce('x_x_method2', array('xcall'));
       $h->Foo->setReturnValue('x_x_method2', '<x>');

       $h->Foo->expectNever('method1');
       $h->Bar->expectOnce('method1', array('a', 'b'));
       $h->Bar->setReturnValue('method1', '<a><b>');

       $h->importHelperMethods('Foo', array('link'));
       $h->importHelperMethods('x', array('x_x_method2'));
       $h->importHelperMethods('Foo', array('method1'));
       $h->importHelperMethods('Bar', array('method1'));

       $this->assertEqual('<a>',
                          _s($h->link("call_link")));
       $this->assertEqual('<x>',
                          _s($h->x_x_method2("xcall")));
       $this->assertEqual('<a><b>',
                          _s($h->method1('a', 'b')));
    }

    function testImportHelperMethodsInUseHelper() {
       $h = $this->h;

       $h->useHelper(array('name' => 'Foo',
                           'prefix' => 'x',
                           'import' => array('link', 'method1', 'x_x_method2')));
       $h->useHelper(array('name' => 'Bar',
                           'import' => array('method1')));

       $h->Foo =& new Mock__HelperForTeset(); //this will be done by the View
       $h->Bar =& new Mock__HelperForTeset(); //this will be done by the View
       $h->beforeRender(); //this will be done by the View

       $h->Foo->expectOnce('link', array('call_link'));
       $h->Foo->setReturnValue('link', '<a>');
       $h->Foo->expectOnce('x_x_method2', array('xcall'));
       $h->Foo->setReturnValue('x_x_method2', '<x>');

       $h->Foo->expectNever('method1');
       $h->Bar->expectOnce('method1', array('a', 'b'));
       $h->Bar->setReturnValue('method1', '<a><b>');



       $this->assertEqual('<a>',
                          _s($h->link("call_link")));
       $this->assertEqual('<x>',
                          _s($h->x_x_method2("xcall")));
       $this->assertEqual('<a><b>',
                          _s($h->method1('a', 'b')));
    }


}
