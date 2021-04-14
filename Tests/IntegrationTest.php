<?php

namespace Pryon\GoogleTranslatorBundle\Tests;

use PHPUnit\Framework\TestCase;
use Pryon\GoogleTranslatorBundle\Service\GoogleTranslator;

class IntegrationTest extends TestCase
{
    /**
     * @var TestKernel
     */
    private $kernel;

    protected function setUp(): void
    {
        $this->kernel = new TestKernel();
        $this->kernel->boot();
    }

    public function testServiceWiring(): void
    {
        $service = $this->kernel->getContainer()->get('pryon.google.translator');
        $this->assertNotNull($service);

        $service = $this->kernel->getContainer()->get(GoogleTranslator::class);
        $this->assertNotNull($service);
    }

    public function testLanguages(): void
    {
        $translator = $this->kernel->getContainer()->get(GoogleTranslator::class);

        $languages = $translator->getSupportedLanguages();
        $this->assertTrue(is_array($languages), 'Check that getSupportedLanguages response is an array');
        $this->assertContains('en', $languages, 'Check that getSupportedLanguages response contains "en"');
        $this->assertContains('fr', $languages, 'Check that getSupportedLanguages response contains "fr"');
    }

    public function testTranslations()
    {
        $translator = $this->kernel->getContainer()->get(GoogleTranslator::class);

        $source = 'I am not in danger.';
        $attended = 'Je ne suis pas en danger.';

        $response = $translator->translate('en', 'fr', $source);
        $this->assertEquals($attended, $response, 'Check string translation');

        $sources = array('I am the one who knocks.', 'I am the danger.');
        $attended = array('Je suis celui qui frappe.', 'Je suis le danger.');

        $response = $translator->translate('en', 'fr', $sources);
        $this->assertEquals($attended, $response, 'Check array translations');
    }
}

