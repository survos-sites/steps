<?php

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: [
                'groups' => [self::READ, self::DETAILS],
            ]
        ),
        new GetCollection(
            normalizationContext: [
                'groups' => [self::READ],
            ]
        )],
    normalizationContext: ['groups' => [self::READ, self::DETAILS, self::RP]],
)]
#[ApiFilter(OrderFilter::class, properties: ['price', 'stock', 'rating'])]
#[ApiFilter(RangeFilter::class, properties: ['rating', 'stock', 'price'])]
#[MeiliIndex(
    primaryKey: 'sku',
    persisted: new Fields(
        groups: [self::READ, self::DETAILS, self::SEARCHABLE]
    ),
    displayed: ['*'],
    filterable: new Fields(
        fields: ['category', 'tags', 'rating', 'price', 'brand'],
    ),
    sortable: ['price', 'rating'],
    searchable: new Fields(
        groups: [self::SEARCHABLE]
    ),
)]
class Product
{
    public const READ = 'product.read';
    public const DETAILS = 'product.details';
    public const SEARCHABLE = 'product.searchable';
    public const RP = 'rp';

    #[Groups(['product.read'])]
    #[ORM\Column(nullable: true)]
    #[Facet(label: 'Category', showMoreThreshold: 12)]
    #[ApiProperty("category from extra, virtual but needs index")]
    public ?string $category;

    // ... rest of class
