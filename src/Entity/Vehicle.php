<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $brand = null;

    #[ORM\Column(length: 255)]
    private ?string $registration = null;

    #[ORM\Column]
    private ?float $dailyPrice = null;

    #[ORM\Column]
    private ?bool $available = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'vehicle')]
    private Collection $reservations;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'vehicle')]
    private Collection $comments;

    /**
     * @var Collection<int, VehicleImage>
     */
    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: VehicleImage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $vehicleImages;

    /**
     * @var Collection<int, Favorite>
     */
    #[ORM\OneToMany(targetEntity: Favorite::class, mappedBy: 'vehicle')]
    private Collection $favorites;

    public function isCurrentlyReserved(): bool
{
    foreach ($this->reservations as $reservation) {
        if ($reservation->getEndDate() > new \DateTime()) {
            return true; // Une réservation en cours → Véhicule indisponible
        }
    }
    return false;
}
    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->vehicleImages = new ArrayCollection();
        $this->favorites = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getRegistration(): ?string
    {
        return $this->registration;
    }

    public function setRegistration(string $registration): static
    {
        $this->registration = $registration;

        return $this;
    }

    public function getDailyPrice(): ?float
    {
        
        return $this->dailyPrice;
    }

    public function setDailyPrice(float $dailyPrice): static
    {
        $this->dailyPrice = $dailyPrice;

        return $this;
    }

    public function getAvailable(): ?bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): self
    {
        $this->available = $available;

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setVehicle($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getVehicle() === $this) {
                $reservation->setVehicle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setVehicle($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getVehicle() === $this) {
                $comment->setVehicle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VehicleImage>
     */
    public function getVehicleImages(): Collection
    {
        return $this->vehicleImages;
    }

    public function addVehicleImage(VehicleImage $vehicleImage): static
    {
        if (!$this->vehicleImages->contains($vehicleImage)) {
            $this->vehicleImages->add($vehicleImage);
            $vehicleImage->setVehicle($this);
        }

        return $this;
    }

    public function removeVehicleImage(VehicleImage $vehicleImage): static
    {
        if ($this->vehicleImages->removeElement($vehicleImage)) {
            // set the owning side to null (unless already changed)
            if ($vehicleImage->getVehicle() === $this) {
                $vehicleImage->setVehicle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setVehicle($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            // set the owning side to null (unless already changed)
            if ($favorite->getVehicle() === $this) {
                $favorite->setVehicle(null);
            }
        }

        return $this;
    }
}
