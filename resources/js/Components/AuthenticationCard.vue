<template>
    <div
        class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-default-background">
        <div>
            <slot name="logo" />
        </div>

        <div
            class="w-full sm:max-w-md mt-6 px-6 py-4 bg-card-background shadow-md border border-card-border overflow-hidden sm:rounded-lg">
            <slot />
        </div>
    </div>
</template>
