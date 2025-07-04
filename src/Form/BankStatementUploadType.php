<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\File;

class BankStatementUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
			->add('csv_file', FileType::class, [
				'label' => 'CSV File',
				'constraints' => [
					new File([
						'maxSize' => '1024k',
						'mimeTypes' => [
							'text/csv',
							'text/plain',
							'application/csv',
							'application/x-csv',
						],
						'mimeTypesMessage' => 'Please upload a valid CSV file',
					])
				],
			])
			->add('upload', SubmitType::class, [
				'label' => 'Upload',
			]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
