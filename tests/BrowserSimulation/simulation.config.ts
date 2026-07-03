export const BASE_URL = process.env.BASE_URL || 'http://localhost:8000';
export const USER_COUNT = parseInt(process.env.SIM_USER_COUNT || '14', 14);
export const EMAIL_PREFIX = process.env.SIM_EMAIL_PREFIX || 'stress_user_';

export type UserPersona = 'sales' | 'manager' | 'admin' | 'guest';

export interface UserConfig {
    id: number;
    email: string;
    password: string;
    persona: UserPersona;
    name: string;
}

export function getPersonaLabel(persona: UserPersona): string {
    switch (persona) {
        case 'sales': return 'Sales Rep';
        case 'manager': return 'Manager';
        case 'admin': return 'Admin';
        case 'guest': return 'Guest';
    }
}

export const userPersonas: UserConfig[] = Array.from({ length: USER_COUNT }, (_, i) => {
    const ratio = i / USER_COUNT;
    let persona: UserPersona;
    if (ratio < 0.4) persona = 'sales';
    else if (ratio < 0.7) persona = 'manager';
    else if (ratio < 0.9) persona = 'admin';
    else persona = 'guest';

    const label = getPersonaLabel(persona);

    return {
        id: i + 1,
        email: `${EMAIL_PREFIX}${i + 1}@test.com`,
        password: 'password',
        persona,
        name: `${label} ${i + 1}`,
    };
});
