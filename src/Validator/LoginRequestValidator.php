<?php

namespace App\Validator;

use App\Exception\ApiException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class LoginRequestValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof LoginRequest) {
            throw new UnexpectedTypeException($constraint, LoginRequest::class);
        }

        $errors = [];
        // Validar email
        if (!isset($value['email']) || !is_string($value['email'])) {
            $this->context->buildViolation('The email field is required and must be a valid string.')
                ->atPath('email')
                ->addViolation();

            $errors['email'][] = 'The email field is required and must be a valid string.';
        } else {
            $emailConstraint = new Assert\Email(['message' => 'The email is not valid.']);
            $emailErrors = $this->context->getValidator()->validate($value['email'], $emailConstraint);

            foreach ($emailErrors as $error) {
                $this->context->buildViolation($error->getMessage())
                    ->setParameter('{{ value }}', $value['email'])
                    ->atPath('email')
                    ->addViolation();

                $errors['email'][] = $error->getMessage();
            }
        }
        // Validar contraseña
        if (!isset($value['password']) || !is_string($value['password'])) {
            $this->context->buildViolation('The password field is required and must be a valid string.')
                ->atPath('password')
                ->addViolation();

            $errors['password'][] = 'The password field is required and must be a valid string.';
        } else {
            $passwordConstraint = new Assert\Length(['min' => 8]);
            $passwordErrors = $this->context->getValidator()->validate($value['password'], $passwordConstraint);

            foreach ($passwordErrors as $error) {
                $this->context->buildViolation($error->getMessage())
                    ->setParameter('{{ value }}', $value['password'])
                    ->addViolation();

                $errors['password'][] = $error->getMessage();
            }
        }

        // Si hay errores, lanzar ApiException para que la capture el ExceptionListener
        if (!empty($errors)) {
            throw new ApiException('Validation error', $errors, 422);
        }
    }
}
