<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservations')]
class Reservation
{
    # AI helped a bit with this entity#
    public const MIN_RENTAL_DAYS = 1;
    public const MAX_RENTAL_DAYS = 30;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_COMPLETED = 'COMPLETED';

    public const PAYMENT_NOT_STARTED = 'not_started';
    public const PAYMENT_ISSUED = 'issued';
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_SUCCESS = 'success';
    public const PAYMENT_REJECTED = 'rejected';
    public const PAYMENT_CANCELLED = 'cancelled';
    public const PAYMENT_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Car $car = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column]
    #[Assert\Positive]
    private ?int $numberOfPersons = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice([
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
    ])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 20)]
    private string $paymentStatus = self::PAYMENT_NOT_STARTED;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $sentooTransactionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sentooPaymentUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sentooQrCodeUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $paymentMessage = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $totalPrice = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): static
    {
        $this->car = $car;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getNumberOfPersons(): ?int
    {
        return $this->numberOfPersons;
    }

    public function setNumberOfPersons(int $numberOfPersons): static
    {
        $this->numberOfPersons = $numberOfPersons;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getRentalDays(): ?int
    {
        if (!$this->startDate || !$this->endDate || $this->endDate <= $this->startDate) {
            return null;
        }

        return $this->startDate->diff($this->endDate)->days;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    public function getSentooTransactionId(): ?string
    {
        return $this->sentooTransactionId;
    }

    public function setSentooTransactionId(?string $sentooTransactionId): static
    {
        $this->sentooTransactionId = $sentooTransactionId;

        return $this;
    }

    public function getSentooPaymentUrl(): ?string
    {
        return $this->sentooPaymentUrl;
    }

    public function setSentooPaymentUrl(?string $sentooPaymentUrl): static
    {
        $this->sentooPaymentUrl = $sentooPaymentUrl;

        return $this;
    }

    public function getSentooQrCodeUrl(): ?string
    {
        return $this->sentooQrCodeUrl;
    }

    public function setSentooQrCodeUrl(?string $sentooQrCodeUrl): static
    {
        $this->sentooQrCodeUrl = $sentooQrCodeUrl;

        return $this;
    }

    public function getPaymentMessage(): ?string
    {
        return $this->paymentMessage;
    }

    public function setPaymentMessage(?string $paymentMessage): static
    {
        $this->paymentMessage = $paymentMessage;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(?string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    #[Assert\Callback]
    public function validateReservationRules(ExecutionContextInterface $context): void
    {
        if (!$this->startDate || !$this->endDate) {
            return;
        }

        if ($this->endDate <= $this->startDate) {
            $context->buildViolation('The end date must be after the start date.')
                ->atPath('endDate')
                ->addViolation();

            return;
        }

        $rentalDays = $this->getRentalDays();

        if ($rentalDays < self::MIN_RENTAL_DAYS || $rentalDays > self::MAX_RENTAL_DAYS) {
            $context->buildViolation(sprintf(
                'The rental period must be between %d and %d days.',
                self::MIN_RENTAL_DAYS,
                self::MAX_RENTAL_DAYS
            ))
                ->atPath('endDate')
                ->addViolation();
        }

        if ($this->car && $this->numberOfPersons !== null && $this->numberOfPersons > $this->car->getSeats()) {
            $context->buildViolation('The number of persons cannot exceed the selected car seat capacity.')
                ->atPath('numberOfPersons')
                ->addViolation();
        }
    }
}
