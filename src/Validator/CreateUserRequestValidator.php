<?php

namespace App\Validator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Exception\ApiException;
use Symfony\Component\Validator\Constraints as Assert;

class CreateUserRequestValidator extends ConstraintValidator
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CreateUserRequest) {
            throw new UnexpectedTypeException($constraint, CreateUserRequest::class);
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $errors = [];
        // Email validation
        if (!isset($value['email']) || !is_string($value['email'])) {
            $errors['email']= 'The email field is required and must be a valid string.';
        } else {
            $emailConstraint = new Assert\Email(['message' => 'The email is not valid.']);
            $emailErrors = $this->context->getValidator()->validate($value['email'], $emailConstraint);

            foreach ($emailErrors as $error) {
                $errors['email'] = $error->getMessage();
            }

            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $value['email']]);
            if ($existingUser) {
                $errors['email'] = 'The email is already in use.';

            }
        }

        // Name validation
        if (empty($value['name']) || strlen($value['name']) > 255) {
            $errors['name'] = 'The name cannot be empty and must have a maximum of 255 characters.';
        }

        // Last name validation
        if (empty($value['lastName']) || strlen($value['lastName']) > 255) {
            $errors['lastName'] = 'The last name cannot be empty and must have a maximum of 255 characters.';
        }

        // Password validation
        if (empty($value['password']) || strlen($value['password']) < 8) {
            $errors['password'] = 'The password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $value['password']) || !preg_match('/[0-9]/', $value['password'])) {
            $errors['password']= 'The password must contain at least one uppercase letter and one number.';
        }

        // If there are errors, throw ApiException to be caught by the ExceptionListener
        if (!empty($errors)) {
            throw new ApiException('Invalid data', $errors, 400);
        }
    }
}
