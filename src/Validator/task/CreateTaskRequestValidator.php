<?php

namespace App\Validator\task;

use App\Entity\User;
use App\Exception\ApiException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use UnexpectedValueException;

final class CreateTaskRequestValidator extends ConstraintValidator
{

    public function __construct(private EntityManagerInterface $entityManager) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof CreateTaskRequest) {
            throw new UnexpectedTypeException($constraint, CreateTaskRequest::class);
        }

        if (!is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        $errors = [];

        // Title validation
        if (empty($value['title']) || !is_string($value['title']) || strlen($value['title']) > 255) {
            $errors['title'] = 'The title is required and must have a maximum of 255 characters.';
        }

        // Description validation (optional)
        if (isset($value['description']) && !is_string($value['description'])) {
            $errors['description'] = 'The description must be a valid string.';
        }

        // Status validation
        $validStatuses = ['pending', 'in_progress', 'completed'];
        if (empty($value['status']) || !in_array($value['status'], $validStatuses, true)) {
            $errors['status'] = 'The status must be one of: pending, in_progress, completed.';
        }

      

        // Assigned To validation
        if (empty($value['assignedTo']) || !is_integer($value['assignedTo'])) {
            $errors['assignedTo'] = 'The assignedTo field is required and must be a valid user ID.';
        } else {
            $assignedUser = $this->entityManager->getRepository(User::class)->find($value['assignedTo']);
            if (!$assignedUser) {
                $errors['assignedTo'] = 'The specified assigned user does not exist.';
            }
        }

        // Throw an exception if validation fails
        if (!empty($errors)) {
            throw new ApiException('Invalid data', $errors, 400);
        }
    }
}
