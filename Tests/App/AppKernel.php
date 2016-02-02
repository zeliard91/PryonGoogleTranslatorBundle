<?php

namespace Pryon\GoogleTranslatorBundle\Tests\App;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\MonologBundle\MonologBundle(),
            new \Pryon\GoogleTranslatorBundle\PryonGoogleTranslatorBundle(),
        );

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return sys_get_temp_dir().'/'.Kernel::VERSION.'/PryonGoogleTranslatorBundle/cache/'.$this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return sys_get_temp_dir().'/'.Kernel::VERSION.'/PryonGoogleTranslatorBundle/logs';
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown()
    {
        parent::shutdown();

        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/'.Kernel::VERSION.'/PryonGoogleTranslatorBundle');
    }
}
