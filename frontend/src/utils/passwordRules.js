/** Must stay in sync with application/Stripedesk/Validation/Password_validator.php */

export const PASSWORD_MIN_LENGTH = 8;
export const PASSWORD_MAX_LENGTH = 128;

export const PASSWORD_HINT =
  `${PASSWORD_MIN_LENGTH}–${PASSWORD_MAX_LENGTH} characters, with uppercase, lowercase, a number, and a special character.`;

/**
 * @returns {{ ok: true } | { ok: false, message: string }}
 */
export function validatePassword(password) {
  if (password === undefined || password === null || password === '') {
    return { ok: false, message: 'Password is required' };
  }
  if (typeof password !== 'string') {
    return { ok: false, message: 'Password is invalid' };
  }
  if (password.length < PASSWORD_MIN_LENGTH) {
    return { ok: false, message: `Password must be at least ${PASSWORD_MIN_LENGTH} characters` };
  }
  if (password.length > PASSWORD_MAX_LENGTH) {
    return { ok: false, message: `Password must be at most ${PASSWORD_MAX_LENGTH} characters` };
  }
  if (!/[A-Z]/.test(password)) {
    return { ok: false, message: 'Password must contain an uppercase letter' };
  }
  if (!/[a-z]/.test(password)) {
    return { ok: false, message: 'Password must contain a lowercase letter' };
  }
  if (!/[0-9]/.test(password)) {
    return { ok: false, message: 'Password must contain a digit' };
  }
  if (!/[^A-Za-z0-9]/.test(password)) {
    return { ok: false, message: 'Password must contain a special character' };
  }
  return { ok: true };
}

/** Ant Design Vue Form `rules` for a password field (create / reset). */
export function passwordFormRules() {
  return [
    { required: true, message: 'Password is required' },
    {
      validator: async (_rule, value) => {
        const r = validatePassword(value);
        if (!r.ok) {
          return Promise.reject(new Error(r.message));
        }
        return Promise.resolve();
      },
    },
  ];
}
