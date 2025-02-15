<?php

namespace TiMacDonald\Website;

use DateTimeImmutable;
use DateTimeZone;

readonly class Page
{
    /**
     * @param  list<Format>  $formats
     */
    public function __construct(
        public string $file,
        public string $image,
        public string $title,
        public string $description,
        public bool $hidden = false,
        public ?string $template = 'page',
        public OgType $ogType = OgType::Website,
        public DateTimeImmutable $date = new DateTimeImmutable('now', new DateTimeZone('Australia/Melbourne')),
        public array $formats = [],
    ) {
        //
    }

    /**
     * @param  list<Format>  $formats
     */
    public static function fromPost(
        string $image,
        string $title,
        string $description,
        string $file,
        DateTimeImmutable $date,
        bool $hidden = false,
        string $template = 'post',
        OgType $ogType = OgType::Article,
        array $formats = [Format::Article],
    ): self {
        return new self(
            file: $file,
            image: $image,
            title: $title,
            description: $description,
            date: $date,
            hidden: $hidden,
            template: $template,
            ogType: $ogType,
            formats: $formats,
        );
    }
}
