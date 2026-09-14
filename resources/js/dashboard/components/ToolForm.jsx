import { useState } from 'react';

const inputClasses =
    'mt-1 block w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30';

const labelClasses = 'block text-sm font-medium text-slate-700';

function Field({ label, name, error, children }) {
    return (
        <div>
            <label htmlFor={name} className={labelClasses}>
                {label}
            </label>
            {children}
            {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
        </div>
    );
}

export default function ToolForm({ initialData = {}, onSubmit, onCancel, isSubmitting }) {
    const [form, setForm] = useState({
        name: initialData.name ?? '',
        slug: initialData.slug ?? '',
        description: initialData.description ?? '',
        type: initialData.type ?? 'extension',
        pricing_model: initialData.pricing_model ?? 'one_time',
        price: initialData.price ?? '',
        device_limit: initialData.device_limit ?? 1,
        referral_credits: initialData.referral_credits ?? '0',
        has_demo: initialData.has_demo ?? false,
        demo_url: initialData.demo_url ?? '',
        is_active: initialData.is_active ?? true,
    });
    const [errors, setErrors] = useState({});

    const handleChange = (event) => {
        const { name, value, type, checked } = event.target;
        setForm((previous) => ({ ...previous, [name]: type === 'checkbox' ? checked : value }));
    };

    const handleSubmit = async (event) => {
        event.preventDefault();
        setErrors({});

        try {
            await onSubmit(form);
        } catch (error) {
            const data = error.response?.data;

            if (data?.errors) {
                setErrors(data.errors);
            } else {
                setErrors({ form: [data?.message || 'Something went wrong. Please try again.'] });
            }
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-5">
            {errors.form && (
                <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {errors.form[0]}
                </div>
            )}

            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Name" name="name" error={errors.name?.[0]}>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        value={form.name}
                        onChange={handleChange}
                        className={inputClasses}
                        placeholder="TimeSync"
                    />
                </Field>

                <Field label="Slug" name="slug" error={errors.slug?.[0]}>
                    <input
                        id="slug"
                        name="slug"
                        type="text"
                        required
                        value={form.slug}
                        onChange={handleChange}
                        className={inputClasses}
                        placeholder="timesync"
                    />
                </Field>
            </div>

            <Field label="Description" name="description" error={errors.description?.[0]}>
                <textarea
                    id="description"
                    name="description"
                    required
                    rows={3}
                    value={form.description}
                    onChange={handleChange}
                    className={inputClasses}
                    placeholder="What does this tool do?"
                />
            </Field>

            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <Field label="Type" name="type" error={errors.type?.[0]}>
                    <select id="type" name="type" value={form.type} onChange={handleChange} className={inputClasses}>
                        <option value="extension">Extension</option>
                        <option value="desktop">Desktop</option>
                    </select>
                </Field>

                <Field label="Pricing model" name="pricing_model" error={errors.pricing_model?.[0]}>
                    <select
                        id="pricing_model"
                        name="pricing_model"
                        value={form.pricing_model}
                        onChange={handleChange}
                        className={inputClasses}
                    >
                        <option value="one_time">One-time</option>
                        <option value="subscription">Subscription</option>
                    </select>
                </Field>
            </div>

            <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <Field label="Price ($)" name="price" error={errors.price?.[0]}>
                    <input
                        id="price"
                        name="price"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                        value={form.price}
                        onChange={handleChange}
                        className={inputClasses}
                        placeholder="19.99"
                    />
                </Field>

                <Field label="Device limit" name="device_limit" error={errors.device_limit?.[0]}>
                    <input
                        id="device_limit"
                        name="device_limit"
                        type="number"
                        min="1"
                        step="1"
                        required
                        value={form.device_limit}
                        onChange={handleChange}
                        className={inputClasses}
                    />
                </Field>

                <Field label="Referral credits" name="referral_credits" error={errors.referral_credits?.[0]}>
                    <input
                        id="referral_credits"
                        name="referral_credits"
                        type="number"
                        min="0"
                        step="0.01"
                        value={form.referral_credits}
                        onChange={handleChange}
                        className={inputClasses}
                    />
                </Field>
            </div>

            <label className="flex items-center gap-3">
                <input
                    id="has_demo"
                    name="has_demo"
                    type="checkbox"
                    checked={form.has_demo}
                    onChange={handleChange}
                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span className="text-sm font-medium text-slate-700">Enable a live demo preview</span>
            </label>

            {form.has_demo && (
                <Field label="Demo URL" name="demo_url" error={errors.demo_url?.[0]}>
                    <input
                        id="demo_url"
                        name="demo_url"
                        type="url"
                        value={form.demo_url}
                        onChange={handleChange}
                        className={inputClasses}
                        placeholder="https://demo.example.com"
                    />
                </Field>
            )}

            <label className="flex items-center gap-3">
                <input
                    id="is_active"
                    name="is_active"
                    type="checkbox"
                    checked={form.is_active}
                    onChange={handleChange}
                    className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span className="text-sm font-medium text-slate-700">Active (visible to customers)</span>
            </label>

            <div className="flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    onClick={onCancel}
                    className="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    disabled={isSubmitting}
                    className="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {isSubmitting ? 'Saving…' : 'Save tool'}
                </button>
            </div>
        </form>
    );
}