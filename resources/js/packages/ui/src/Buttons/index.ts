import { cva, type VariantProps } from 'class-variance-authority';

export { default as Button } from './Button.vue';

export const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                default: 'bg-primary text-primary-foreground shadow hover:bg-primary/90',
                destructive:
                    'bg-destructive text-destructive-foreground shadow-sm hover:bg-destructive/90',
                outline:
                    'border shadow-xs hover:text-text-primary bg-card-background dark:bg-transparent border-input dark:border-input hover:bg-white/5',
                secondary: 'bg-secondary text-secondary-foreground shadow-sm hover:bg-secondary/80',
                ghost: 'hover:bg-white/5',
                link: 'text-primary underline-offset-4 hover:underline',
                input: 'border-input-border border bg-input-background text-text-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent shadow-sm',
            },
            size: {
                default: 'h-9 px-3 text-sm',
                xs: 'h-7 rounded px-2 text-xs',
                sm: 'h-8 rounded-md px-3 text-xs',
                lg: 'h-10 rounded-md px-4',
                icon: 'h-9 w-9',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    }
);

export type ButtonVariants = VariantProps<typeof buttonVariants>;
