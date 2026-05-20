<?php

namespace App\Entity;

use App\Repository\CarRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarRepository::class)]
#[ORM\Table(name: 'cars')]
#[ORM\UniqueConstraint(name: 'UNIQ_CARS_CODE', columns: ['code'])]
class Car
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $code = null;

    #[ORM\Column(length: 30)]
    private ?string $category = null;

    #[ORM\Column(length: 60)]
    private ?string $make = null;

    #[ORM\Column(length: 80)]
    private ?string $model = null;

    #[ORM\Column(name: 'model_year')]
    private ?int $modelYear = null;

    #[ORM\Column]
    private ?int $seats = null;

    #[ORM\Column]
    private ?int $doors = null;

    #[ORM\Column(length: 20)]
    private ?string $transmission = null;

    #[ORM\Column(name: 'fuel_type', length: 20)]
    private ?string $fuelType = null;

    #[ORM\Column(name: 'luggage_capacity')]
    private ?int $luggageCapacity = null;

    #[ORM\Column(length: 60)]
    private ?string $color = null;

    #[ORM\Column(name: 'daily_base_price', type: 'decimal', precision: 10, scale: 2)]
    private ?string $dailyBasePrice = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(name: 'image_filename', length: 255)]
    private ?string $imageFilename = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getMake(): ?string
    {
        return $this->make;
    }

    public function setMake(string $make): static
    {
        $this->make = $make;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getModelYear(): ?int
    {
        return $this->modelYear;
    }

    public function setModelYear(int $modelYear): static
    {
        $this->modelYear = $modelYear;

        return $this;
    }

    public function getSeats(): ?int
    {
        return $this->seats;
    }

    public function setSeats(int $seats): static
    {
        $this->seats = $seats;

        return $this;
    }

    public function getDoors(): ?int
    {
        return $this->doors;
    }

    public function setDoors(int $doors): static
    {
        $this->doors = $doors;

        return $this;
    }

    public function getTransmission(): ?string
    {
        return $this->transmission;
    }

    public function setTransmission(string $transmission): static
    {
        $this->transmission = $transmission;

        return $this;
    }

    public function getFuelType(): ?string
    {
        return $this->fuelType;
    }

    public function setFuelType(string $fuelType): static
    {
        $this->fuelType = $fuelType;

        return $this;
    }

    public function getLuggageCapacity(): ?int
    {
        return $this->luggageCapacity;
    }

    public function setLuggageCapacity(int $luggageCapacity): static
    {
        $this->luggageCapacity = $luggageCapacity;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getDailyBasePrice(): ?string
    {
        return $this->dailyBasePrice;
    }

    public function setDailyBasePrice(string $dailyBasePrice): static
    {
        $this->dailyBasePrice = $dailyBasePrice;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }
}
