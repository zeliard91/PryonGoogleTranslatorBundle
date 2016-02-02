<?php

namespace Pryon\GoogleTranslatorBundle\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GoogleTranslatorTest extends WebTestCase
{
    protected static $class = 'Pryon\GoogleTranslatorBundle\Tests\App\AppKernel';

    private $translator;

    /**
     * Init translator service for each test method.
     */
    public function setUp()
    {
        $kernel = $this->createKernel();
        $kernel->boot();
        $this->translator = $kernel->getContainer()->get('pryon.google.translator');
    }

    /**
     * Test get supported languages call.
     *
     * @return [type] [description]
     */
    public function testLanguages()
    {
        $languages = $this->translator->getSupportedLanguages();

        $this->assertTrue(is_array($languages), 'Check that getSupportedLanguages response is an array');

        $this->assertContains('en', $languages, 'Check that getSupportedLanguages response contains "en"');

        $this->assertContains('fr', $languages, 'Check that getSupportedLanguages response contains "fr"');
    }

    /**
     * Test translations calls.
     *
     * @return [type] [description]
     */
    public function testTranslations()
    {
        $source = 'I am not in danger.';
        $attended = 'Je ne suis pas en danger.';

        $response = $this->translator->translate('en', 'fr', $source);
        $this->assertEquals($attended, $response, 'Check string translation');

        $sources = array('I am the one who knocks.', 'I am the danger.');
        $attended = array('Je suis celui qui frappe.', 'Je suis le danger.');

        $response = $this->translator->translate('en', 'fr', $sources);
        $this->assertEquals($attended, $response, 'Check array translations');
    }
}
