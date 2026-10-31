type IconProps = {
    name: string;
    className?: string;
};

const MONO_DARK_BRANDS = new Set(['chatgpt', 'wikipedia']);

export function Icon({ name, className = 'size-5' }: IconProps) {
    if (name.startsWith('brand:')) {
        const brandName = name.replace('brand:', '');
        const invertClass = MONO_DARK_BRANDS.has(brandName) ? 'dark:invert' : '';

        return (
            <img
                src={`/icons/brands/${brandName}.svg`}
                alt={brandName}
                className={`inline-block shrink-0 object-contain pointer-events-none select-none ${invertClass} ${className}`}
                loading="lazy"
            />
        );
    }

    const mask = `url(/icons/tabler/${name}.svg) center / contain no-repeat`;

    return (
        <span
            aria-hidden="true"
            className={`inline-block shrink-0 bg-current ${className}`}
            style={{ WebkitMask: mask, mask }}
        />
    );
}
