<?php

namespace Pryon\GoogleTranslatorBundle\Tests;

use PHPUnit\Framework\TestCase;
use Pryon\GoogleTranslatorBundle\Form\Type\LanguageType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;

class LanguageTypeTest extends TestCase
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

    public function testType()
    {
        $builder = $this->kernel->getContainer()->get('form.factory')->createBuilder();

        $builder->add('source', LanguageType::class);

        $form = $builder->getForm();

        $this->assertNotNull($form);
        $this->assertCount(1, $form);

        $view = $form->createView();
        $this->assertEquals(1, $view->count());
        
        $hasFr = false;
        $hasEn = false;
        foreach ($view as $rowView) {
            foreach ($rowView->vars['choices'] as $choice) {
                if ('fr' === $choice->value) {
                    $hasFr = true;
                }
                if ('en' === $choice->value) {
                    $hasEn = true;
                }
            }
        }
        $this->assertTrue($hasFr, 'French is in choice list');
        $this->assertTrue($hasEn, 'English is in choice list');
    }
}