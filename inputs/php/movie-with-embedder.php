<?php

#[MeiliIndex(
// serialization groups for the JSON sent to the index
    primaryKey: 'sku',
    searchable: new Fields(
        groups: ['product.searchable']
    ),
    embedders: ['product']
)]
