<?php
/**
 * Language Form Type based on Google Translator supported languages
 */
namespace Pryon\GoogleTranslatorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Pryon\GoogleTranslatorBundle\Service\GoogleTranslator;

class LanguageType extends AbstractType
{
    private $Translator;

    private $choices = null;

    public function __construct(GoogleTranslator $Translator)
    {
        $this -> Translator = $Translator;
    }

    /**
     * Return choices based on google supported languages
     * @return [type] [description]
     */
    private function getChoices()
    {
        if (is_null($this -> choices))
        {
            $this -> choices = array();
            foreach($this -> Translator -> getSupportedLanguages() as $language)
            {
                if (Intl::getLanguageBundle() -> getLanguageName($language) != '')
                {
                    $this -> choices[$language] = Intl::getLanguageBundle() -> getLanguageName($language);
                }
            }
            $collator = new \Collator(\Locale::getDefault());
            $collator -> asort($this -> choices);
        }
        return $this -> choices;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this -> getChoices(),
            // 'choices' => array(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'translatorlanguage';
    }
}