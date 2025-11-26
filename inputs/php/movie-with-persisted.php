<?php
#[MeiliIndex(
    persisted: ['*'],
    primaryKey: 'imdbId',
    searchable: ['title','overview'],
    filterable: ['year', 'budget', 'genres'],
    sortable: ['year', 'budget'],
)]
#[ORM\Entity(repositoryClass: MovieRepository::class)]
class Movie
