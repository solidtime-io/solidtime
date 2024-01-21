export {};
declare global {
    export namespace inertia {
        export interface Props {
            user: {
                id: number;
                name: string;
                email: string;
                created_at: Date;
                updated_at: Date;
            };
            jetstream: {
                [key: string]: boolean;
            };
            errorBags: unknown;
            errors: unknown;
        }
    }
}
