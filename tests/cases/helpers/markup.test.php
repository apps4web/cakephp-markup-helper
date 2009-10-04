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

App::import('Helper', 'markup');

function _s($obj)
{
  return strval($obj);
}

class MarkupTestCase extends CakeTestCase
{
  var $h;

  function startTest()
  {
    $this->h = new MarkupHelper();
  }

  function testStartTag()
  {
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

  function testStartTagChain()
  {
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

  function testEndTag()
  {
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

  function testEndTagWithExplicitTagName()
  {
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

  function testEndTagError()
  {
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

  function testEndAllTags()
  {
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

  function testClear()
  {
    $h = $this->h;

    $h->startTag('div')->startTag('p');
    $h->clear();
    $this->assertEqual('', _s($h));
    $this->assertEqual('', _s($h->endTag()));
  }

  function testNewline()
  {
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

  function testEmptyElements()
  {
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

  function testText()
  {
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

  function testHtml()
  {
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

  function testPushAndPopContext()
  {
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

  function testShortCutMethods1()
  {
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

  function testShortCutMethods2()
  {
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

}
