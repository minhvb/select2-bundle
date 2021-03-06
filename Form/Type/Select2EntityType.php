<?php

namespace Tetranz\Select2Bundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;
use Tetranz\Select2Bundle\Form\DataTransformer\EntitiesToPropertyTransformer;
use Tetranz\Select2Bundle\Form\DataTransformer\EntityToPropertyTransformer;

/**
 *
 * Class Select2EntityType
 * @package Tetranz\Select2Bundle\Form\Type
 */
class Select2EntityType extends Select2AbstractType
{
    public function __construct(Registry $doctrine, RouterInterface $router, $minimumInputLength, $pageLimit, $allowClear, $delay, $language, $cache)
    {
        return parent::__construct($doctrine, $router, $minimumInputLength, $pageLimit, $allowClear, $delay, $language, $cache);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $em = $this->doctrine->getEntityManager($options['entity_manager']);

        // add custom data transformer
        if ($options['transformer']) {
            if (!is_string($options['transformer'])) {
                throw new \Exception('The option transformer must be a string');
            }
            if (!class_exists($options['transformer'])) {
                throw new \Exception('Unable to load class: '.$options['transformer']);
            }

            $transformer = new $options['transformer']($em, $options['class']);

            if (!$transformer instanceof DataTransformerInterface) {
                throw new \Exception(sprintf('The custom transformer %s must implement "Symfony\Component\Form\DataTransformerInterface"', get_class($transformer)));
            }

        // add the default data transformer
        } else {
            $transformer = $options['multiple']
                ? new EntitiesToPropertyTransformer($em, $options['class'], $options['text_property'], $options['primary_key'])
                : new EntityToPropertyTransformer($em, $options['class'], $options['text_property'], $options['primary_key']);
        }

        $builder->addViewTransformer($transformer, true);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
        // make variables available to the view
        $view->vars['remote_path'] = $options['remote_path']
            ?: $this->router->generate($options['remote_route'], array_merge($options['remote_params'], [ 'page_limit' => $options['page_limit'] ]));

        $varNames = array('multiple', 'minimum_input_length', 'placeholder', 'language', 'allow_clear', 'delay', 'language', 'cache', 'primary_key');
        foreach ($varNames as $varName) {
            $view->vars[$varName] = $options[$varName];
        }

        if ($options['multiple']) {
            $view->vars['full_name'] .= '[]';
        }
    }

    /**
     * Added for pre Symfony 2.7 compatibility
     *
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'class' => null,
                'entity_manager' => null,
                'primary_key' => 'id',
                'remote_path' => null,
                'remote_route' => null,
                'remote_params' => array(),
                'multiple' => false,
                'compound' => false,
                'minimum_input_length' => $this->minimumInputLength,
                'page_limit' => $this->pageLimit,
                'allow_clear' => $this->allowClear,
                'delay' => $this->delay,
                'text_property' => null,
                'placeholder' => '',
                'language' => $this->language,
                'required' => false,
                'cache' => $this->cache,
                'transformer' => null,
            )
        );
    }

    /**
     * pre Symfony 3 compatibility
     *
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * Symfony 2.8+
     *
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'tetranz_select2entity';
    }
}
