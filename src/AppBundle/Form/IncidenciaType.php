<?php

namespace AppBundle\Form;
use ESocial\UtilBundle\Form\ESocialType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class IncidenciaType
 * @package AppBundle\Form
 * @author Débora Vázquez Lara <debora.vazquez@gmail.com>
 */
class IncidenciaType extends ESocialType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $data = array_key_exists('data', $options) ? $options['data'] : null;
        $em = $this->getManager();
        $post = $this->getPost();

        $builder->addEventListener(FormEvents::POST_SET_DATA, function(FormEvent $event) use ($options, $builder, $em, $data){
            $data = $event->getData();
            $form = $event->getForm();


            if ($data && $data->getCodigoUser()) {
                $user = $em->getRepository('VallasModelBundle:User')->findOneBy(array('codigo' => $data->getCodigoUserAsignado()));
                $form->get('user')->setData($user);
            }


        });

        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) use ($options, $builder) {
            $data = $event->getData();
            $form = $event->getForm();

            if ($data->getEstadoIncidencia() != 2){
                $form->getData()->setFechaCierre(null);
                $form->getData()->setObservacionesCierre(null);
            }

        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use ($options, $builder, $em){
            $data = $event->getData();
            $form = $event->getForm();

            if (!$data) return;

            if ($form->getData() && $data && $data['user']) {
                $user = $em->getRepository('VallasModelBundle:User')->find($data['user']);
                $form->getData()->setCodigoUserAsignado($user->getCodigo());
            }

            if (intval($data['estado_incidencia']) == 2 && $form->getData()->getEstadoIncidencia() != intval($data['estado_incidencia'])) {
                ESocialType::addOptionsToEmbedFormField($builder, $form, 'fecha_cierre', array('constraints' => array(new NotBlank())));
            }

        });

        $builder->add('medio', 'selectable_entity', array(
                'label' => 'form.incidencia.label.medio',
                'class' => 'VallasModelBundle:Medio',
                'required' => false,
                'select_text'   => 'Select Medio',
                'enable_update' => true
        ));

        $builder->add('user', 'entity', array(
            'mapped' => false,
            'constraints' => array(new NotBlank()),
            'label' => 'form.incidencia.label.user_assigned',
            'empty_value' => 'form.label.choice_empty_value',
            'class' => 'VallasModelBundle:User', 'required' => true,
            'query_builder' => function ($repository){ return $repository->getQueryBuilder()->leftJoin('u.user_paises', 'up'); })
        );

        $builder->add('fecha_limite', 'date', array('label' => 'form.incidencia.label.fecha_limite', 'widget' => 'single_text',
            'format' => 'dd/MM/yyyy', 'constraints' => array(new NotBlank()), 'attr' => array('class' => 'calendar text-date')));

        $builder
            ->add('estado_incidencia', 'choice', array('label' => 'form.incidencia.label.estado_incidencia', 'empty_value' => 'form.label.choice_empty_value', 'choices' => array(
            '0' => 'Pendiente', '1' => 'En proceso', '2' => 'Cerrada'), 'constraints' => array(new NotBlank())));

        $builder
            ->add('tipo', 'choice', array('label' => 'form.incidencia.label.tipo', 'empty_value' => 'form.label.choice_empty_value',
                'choices' => array('0' => 'Iluminación', '1' => 'Fijación', '2' => 'Instalación', '3' => 'Otros'),
                'constraints' => array(new NotBlank())));

        $builder->add('observaciones', 'textarea', array('label' => 'form.incidencia.label.observaciones', 'required' => false));

        $estadoIncidencia = $data->getEstadoIncidencia();
        if ($post) $estadoIncidencia = $post['estado_incidencia'];
        if ($estadoIncidencia == 2){
            $builder
                ->add('fecha_cierre', 'date', array('label' => 'form.incidencia.label.fecha_cierre', 'widget' => 'single_text', 'required' => true,
                    'format' => 'dd/MM/yyyy', 'attr' => array('class' => 'calendar text-date')))
                ->add('observaciones_cierre', 'textarea', array('label' => 'form.incidencia.label.observaciones_cierre', 'required' => false));
        }

    }

    public function getName()
    {
        if ($this->_form_name) return $this->_form_name;
        return 'incidencia';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Vallas\ModelBundle\Entity\Incidencia'));
    }

}