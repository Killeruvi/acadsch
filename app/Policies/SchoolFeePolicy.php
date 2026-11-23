<?php

namespace App\Policies;

use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\User;

class SchoolFeePolicy
{
    /**
     * Determine whether the user can view fee structures or invoices.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create fee structures or generate invoices.
     */
    public function manageStructures(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can generate class fee invoices.
     */
    public function generateInvoices(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can pay or settle a fee invoice.
     */
    public function pay(User $user, FeeInvoice $invoice): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return $user->id === $invoice->student_id;
        }

        if ($user->isParent()) {
            $student = $invoice->student;

            return $student && (
                $student->parent_id === $user->id ||
                $student->guardian_email === $user->email
            );
        }

        return false;
    }

    /**
     * Determine whether the user can view a payment receipt.
     */
    public function viewReceipt(User $user, FeePayment $payment): bool
    {
        if ($user->isAdmin() || $user->isTeacher()) {
            return true;
        }

        if ($user->isStudent()) {
            return $payment->student_id === $user->id;
        }

        if ($user->isParent()) {
            $student = $payment->invoice->student ?? null;

            return $student && (
                $student->parent_id === $user->id ||
                $student->guardian_email === $user->email
            );
        }

        return false;
    }
}
