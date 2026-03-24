import axios from 'axios';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEventHandler } from 'react';
import Header from '../components/Header';
import type { PaginatedResponse, SharedProps, Transaction } from '../types';

interface TransactionProps extends SharedProps {
    name: string;
    transactions: PaginatedResponse<Transaction>;
    filters: {
        from: string | null;
        to: string | null;
    };
}

function formatAmount(amount: number, currency: string): string {
    return new Intl.NumberFormat('en-GB', { style: 'currency', currency }).format(amount / 100);
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString('en-GB', { dateStyle: 'medium', timeStyle: 'short' });
}

export default function Transactions() {
    const { name, transactions, filters } = usePage<TransactionProps>().props;

    const handleFilter: FormEventHandler = (e) => {
        e.preventDefault();
        const form = e.currentTarget as HTMLFormElement;
        const from = (form.elements.namedItem('from') as HTMLInputElement).value;
        const to = (form.elements.namedItem('to') as HTMLInputElement).value;
        router.get('/transactions', { from: from || undefined, to: to || undefined }, { replace: true });
    };

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Header */}
            <Header name={name} />

            {/* Transaction list */}
            <section className="rounded-lg p-6 max-w-5xl mx-auto px-4 py-8 space-y-8 flex justify-center items-center pt-10 md:pt-20 flex-col">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Transactions</h2>

                {/* Date range filter */}
                <form onSubmit={handleFilter} className="flex flex-wrap gap-3 mb-6 items-end">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">From</label>
                        <input
                            type="date"
                            name="from"
                            defaultValue={filters.from ?? ''}
                            className="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">To</label>
                        <input
                            type="date"
                            name="to"
                            defaultValue={filters.to ?? ''}
                            className="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                    <button
                        type="submit"
                        className="bg-gray-800 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-700 transition"
                    >
                        Filter
                    </button>
                    {(filters.from || filters.to) && (
                        <button
                            type="button"
                            onClick={() => router.get('/transactions', {}, { replace: true })}
                            className="text-sm text-gray-500 hover:text-gray-700 underline"
                        >
                            Clear
                        </button>
                    )}
                </form>

                {transactions.data.length === 0 ? (
                    <p className="text-sm text-gray-500">No transactions found.</p>
                ) : (
                    <>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm text-left">
                                <thead className="bg-gray-50 text-gray-500 uppercase text-xs">
                                    <tr>
                                        <th className="px-4 py-3">Date</th>
                                        <th className="px-4 py-3">Amount</th>
                                        <th className="px-4 py-3">Card</th>
                                        <th className="px-4 py-3">Description</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">Reference</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {transactions.data.map((tx) => (
                                        <tr key={tx.id} className="hover:bg-gray-50">
                                            <td className="px-4 py-3 text-gray-600 whitespace-nowrap">
                                                {formatDate(tx.created_at)}
                                            </td>
                                            <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                {formatAmount(tx.amount, tx.currency)}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600">
                                                •••• {tx.card_last_four}
                                            </td>
                                            <td className="px-4 py-3 text-gray-600 max-w-xs truncate">
                                                {tx.description ?? '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span
                                                    className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${
                                                        tx.status === 'succeeded'
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800'
                                                    }`}
                                                >
                                                    {tx.status}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-gray-400 text-xs font-mono">
                                                {tx.psp_reference}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {transactions.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between text-sm text-gray-600">
                                <span>
                                    Showing {transactions.from}–{transactions.to} of {transactions.total}
                                </span>
                                <div className="flex gap-2">
                                    {transactions.current_page > 1 && (
                                        <button
                                            onClick={() =>
                                                router.get('/transactions', {
                                                    ...(filters.from ? { from: filters.from } : {}),
                                                    ...(filters.to ? { to: filters.to } : {}),
                                                    page: transactions.current_page - 1,
                                                })
                                            }
                                            className="px-3 py-1 border rounded hover:bg-gray-50"
                                        >
                                            ← Prev
                                        </button>
                                    )}
                                    {transactions.current_page < transactions.last_page && (
                                        <button
                                            onClick={() =>
                                                router.get('/transactions', {
                                                    ...(filters.from ? { from: filters.from } : {}),
                                                    ...(filters.to ? { to: filters.to } : {}),
                                                    page: transactions.current_page + 1,
                                                })
                                            }
                                            className="px-3 py-1 border rounded hover:bg-gray-50"
                                        >
                                            Next →
                                        </button>
                                    )}
                                </div>
                            </div>
                        )}
                    </>
                )}
            </section>
        </div>
    );
}
