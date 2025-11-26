<?php

#[ApiFilter(filterClass: SearchFilter::class, properties: self::FILTERABLE_FIELDS)]
#[ApiFilter(filterClass: OrderFilter::class, properties: self::SORTABLE_FIELDS)]
#[MeiliIndex(
    primaryKey: 'code',
    filterable: self::FILTERABLE_FIELDS,
    sortable: self::SORTABLE_FIELDS,
    searchable: self::SEARCHABLE_FIELDS,
)]
final class Wine
{
    public const FILTERABLE_FIELDS = ['year', 'type', 'price', 'quality'];
    public const SORTABLE_FIELDS = ['year', 'quantity', 'price', 'quality'];
    public const SEARCHABLE_FIELDS = ['name','description'];

