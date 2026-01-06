<?php

namespace App\Form;

use App\Entity\Employee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{
    ChoiceType, DateTimeType, EmailType, IntegerType, TextType, CheckboxType
};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class EmployeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class)
            ->add('lastname', TextType::class)
            ->add('email', EmailType::class)
            ->add('birthdate', DateTimeType::class)
            ->add('active', CheckboxType::class, ['required' => false])
            ->add('employedSince', DateTimeType::class)
            ->add('employedUntil', DateTimeType::class, ['required' => false])
            ->add('hours', ChoiceType::class, [
                'choices' => [
                    '8h' => '8',
                    '6h' => '6',
                    '4h' => '4',
                ]
            ])
            ->add('salary', IntegerType::class)
            ->add('position', ChoiceType::class, [
                'choices' => [
                    'Manager' => 'manager',
                    'Account Manager' => 'account_manager',
                    'QA Manager' => 'qa_manager',
                    'Dev Manager' => 'dev_manager',
                    'CEO' => 'ceo',
                    'COO' => 'coo',
                    'Backend Dev' => 'backend_dev',
                    'Frontend Dev' => 'frontend_dev',
                    'QA Tester' => 'qa_tester',
                ]
            ])
            ->add('manager', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => fn(Employee $e) => $e->getFirstname().' '.$e->getLastname(),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employee::class,
        ]);
    }
}