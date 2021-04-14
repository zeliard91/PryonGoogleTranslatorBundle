<?php
/**
 * Language Form Type based on Google Translator supported languages.
 */
namespace Pryon\GoogleTranslatorBundle\Form\Type;

use Pryon\GoogleTranslatorBundle\Service\GoogleTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Intl\Languages;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LanguageType extends AbstractType
{
    private $Translator;

    private $choices = null;

    public function __construct(GoogleTranslator $Translator)
    {
        $this->Translator = $Translator;
    }

    /**
     * Return choices based on google supported languages.
     *
     * @return [type] [description]
     */
    private function getChoices()
    {
        if (is_null($this->choices)) {
            $this->choices = array();
            $supportedLanguages = $this->Translator->getSupportedLanguages();
            $labelLanguages = Languages::getNames();
            foreach ($supportedLanguages as $language) {
                if (isset($labelLanguages[$language])) {
                    $this->choices[$language] = $labelLanguages[$language];
                }
            }
            $collator = new \Collator(\Locale::getDefault());
            $collator->asort($this->choices);
        }
        $this->choices = array_flip($this->choices);

        return $this->choices;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->getChoices(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'translatorlanguage';
    }
}
