<?php

namespace TiMacDonald\Website;

use DateTimeImmutable;
use DateTimeZone;

readonly class Page
{
    public function __construct(
        public string $file,
        public string $image,
        public string $title,
        public string $description,
        public bool $hidden = false,
        public Template $template = Template::Page,
        public OgType $ogType = OgType::Website,
        public DateTimeImmutable $date = new DateTimeImmutable('now', new DateTimeZone('Australia/Melbourne')),
        public ?Format $format = null,
        public ?string $externalLink = null,
    ) {
        //
    }

    public static function fromPost(
        string $image,
        string $title,
        string $description,
        string $file,
        DateTimeImmutable $date,
        bool $hidden = false,
        Template $template = Template::Post,
        OgType $ogType = OgType::Article,
        Format $format = Format::Article,
        ?string $externalLink = null,
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
            externalLink: $externalLink,
        );
    }
}
