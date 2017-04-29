<?php
/**
 * Language Form Type based on Google Translator supported languages.
 */
namespace Pryon\GoogleTranslatorBundle\Form\Type;

use Pryon\GoogleTranslatorBundle\Service\GoogleTranslator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
            foreach ($this->Translator->getSupportedLanguages() as $language) {
                if (Intl::getLanguageBundle()->getLanguageName($language) != '') {
                    $this->choices[$language] = Intl::getLanguageBundle()->getLanguageName($language);
                }
            }
            $collator = new \Collator(\Locale::getDefault());
            $collator->asort($this->choices);
        }

        if (-1 !== version_compare(Kernel::VERSION, '2.8')) {
            $this->choices = array_flip($this->choices);
        }

        return $this->choices;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
        if (-1 == version_compare(Kernel::VERSION, '2.8')) {
            return 'choice';
        } else {
            return ChoiceType::class;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'translatorlanguage';
    }
}
