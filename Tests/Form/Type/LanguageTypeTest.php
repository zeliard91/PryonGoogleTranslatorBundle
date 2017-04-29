<?php

namespace Pryon\GoogleTranslatorBundle\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pryon\GoogleTranslatorBundle\Form\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\HttpKernel\Kernel;

class LanguageTypeTest extends WebTestCase
{
    protected static $class = 'Pryon\GoogleTranslatorBundle\Tests\App\AppKernel';

    /**
     * Init translator service for each test method.
     */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
    }

    public function testType()
    {
        $builder = $form = static::$kernel->getContainer()->get('form.factory')->createBuilder();

        if (-1 == version_compare(Kernel::VERSION, '2.8')) {
            $builder->add('source', 'translatorlanguage');
        } else {
            $builder->add('source', LanguageType::class);
        }

        $form = $builder->getForm();

        $this->assertNotNull($form);
        $this->assertCount(1, $form);
    }
}