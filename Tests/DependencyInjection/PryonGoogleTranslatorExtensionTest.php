<?php

namespace Pryon\GoogleTranslatorBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Pryon\GoogleTranslatorBundle\DependencyInjection\PryonGoogleTranslatorExtension;

class PryonGoogleTranslatorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Extension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->extension = new PryonGoogleTranslatorExtension();
        $this->container = new ContainerBuilder();
        $this->container->registerExtension($this->extension);
    }

    /**
     * @return array
     */
    private function getMinimalConfiguration()
    {
        return array('google_api_key' => '123456789');
    }

    /**
     * @param string $value
     * @param string $key
     */
    private function assertAlias($value, $key)
    {
        $this->assertEquals($value, (string) $this->container->getAlias($key), sprintf('%s alias is correct', $key));
    }

    public function testMissingConfiguration()
    {
        $this->container->loadFromExtension($this->extension->getAlias());
        $this->setExpectedException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');
        $this->container->compile();
    }

    public function testMinimalConfiguration()
    {
        $this->container->loadFromExtension($this->extension->getAlias(), $this->getMinimalConfiguration());
        $this->container->compile();

        $this->assertTrue($this->container->has('pryon.google.translator'), 'is service loaded');
    }

    /**
     * Test Doctrine cache injection.
     */
    public function testCacheInjection()
    {
        $definition = new Definition('Doctrine\Common\Cache\PhpFileCache');
        $this->container->setDefinition('test.doctrine', $definition);

        $config = $this->getMinimalConfiguration();
        $config['cache'] = array('service' => 'test.doctrine');

        $this->container->loadFromExtension($this->extension->getAlias(), $config);
        $this->container->compile();

        $this->assertTrue($this->container->has('pryon.google.translator'), 'is service loaded');
        $this->assertAlias('test.doctrine', 'pryon.google.translator.cache_provider');
    }
}
