import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState, useEffect } from 'react';
import axios from 'axios';
import CategorySummary from '@/Components/CategorySummary';

export default function View({ auth, entries, groupedByType, csvFileId, categories: initialCategories }) {
    console.log('Initial categories:', initialCategories); // Debug log

    const [hideZeroEntries, setHideZeroEntries] = useState(() => {
        const saved = localStorage.getItem('hideZeroEntries');
        return saved ? JSON.parse(saved) : false;
    });

    const [groupEntries, setGroupEntries] = useState(() => {
        const saved = localStorage.getItem('groupEntries');
        return saved ? JSON.parse(saved) : false;
    });

    const [expandedGroups, setExpandedGroups] = useState(() => {
        const saved = localStorage.getItem('expandedGroups');
        return saved ? new Set(JSON.parse(saved)) : new Set();
    });

    const [categories, setCategories] = useState(initialCategories);
    
    console.log('Categories state:', categories); // Debug log

    const [selectedCategory, setSelectedCategory] = useState(null);
    const [assigningCategory, setAssigningCategory] = useState(false);

    const [excludedEntries, setExcludedEntries] = useState(() => {
        const saved = localStorage.getItem('excludedEntries');
        return saved ? new Set(JSON.parse(saved)) : new Set();
    });

    useEffect(() => {
        localStorage.setItem('hideZeroEntries', JSON.stringify(hideZeroEntries));
        localStorage.setItem('groupEntries', JSON.stringify(groupEntries));
        localStorage.setItem('excludedEntries', JSON.stringify(Array.from(excludedEntries)));
        localStorage.setItem('expandedGroups', JSON.stringify(Array.from(expandedGroups)));
    }, [hideZeroEntries, groupEntries, excludedEntries, expandedGroups]);

    const assignCategory = async (entry, categoryId) => {
        setAssigningCategory(true);
        try {
            const entryIds = entry.isGroup ? entry.children.map(child => child.id) : [entry.id];
            await axios.post('/assign-category', {
                entry_ids: entryIds,
                category_id: categoryId
            });

            // Update local state
            const updateEntryCategories = (entry) => {
                const category = categories.find(c => c.id === categoryId);
                
                // Reset categories array to only contain the new category
                entry.categories = [category];
            };

            if (entry.isGroup) {
                entry.children.forEach(child => {
                    updateEntryCategories(child);
                });
            } else {
                updateEntryCategories(entry);
            }

            // Force a re-render
            setCategories([...categories]);
        } catch (error) {
            console.error('Failed to assign category:', error);
        } finally {
            setAssigningCategory(false);
        }
    };

    const toggleGroup = (groupId) => {
        setExpandedGroups(prev => {
            const newSet = new Set(Array.from(prev));
            if (newSet.has(groupId)) {
                newSet.delete(groupId);
            } else {
                newSet.add(groupId);
            }
            return newSet;
        });
    };

    const toggleExcludeEntry = (entry) => {
        const entryId = entry.isGroup ? `group-${entry.groupId}` : `entry-${entry.id}`;
        
        setExcludedEntries(prev => {
            const newSet = new Set(prev);
            if (newSet.has(entryId)) {
                newSet.delete(entryId);
            } else {
                newSet.add(entryId);
            }
            return newSet;
        });
    };

    const isEntryExcluded = (entry) => {
        const entryId = entry.isGroup ? `group-${entry.groupId}` : `entry-${entry.id}`;
        return excludedEntries.has(entryId);
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString();
    };

    const formatAmount = (amount, currency = 'PLN') => {
        const currencyCode = currency || 'PLN';
        const expenseAmount = amount > 0 ? -amount : amount;
        return new Intl.NumberFormat('pl-PL', {
            style: 'currency',
            currency: currencyCode,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(expenseAmount);
    };

    const getEntriesKey = (entry) => {
        const description = entry.description?.trim();
        if (!description) return null;
        
        // If recipient exists, include it in the key
        if (entry.recipient?.trim()) {
            return `${description}-${entry.recipient.trim()}`;
        }
        
        return description;
    };

    const filterEntries = (entries) => {
        if (!entries) return [];
        
        if (groupedByType) {
            // If entries are grouped by type, filter each group
            const filteredGroups = {};
            Object.entries(entries).forEach(([type, typeEntries]) => {
                const filtered = typeEntries.filter(entry => 
                    !hideZeroEntries || parseFloat(entry.amount) !== 0
                );
                if (filtered.length > 0) {
                    filteredGroups[type] = filtered;
                }
            });
            return filteredGroups;
        } else {
            // If entries are not grouped, filter directly
            return entries.filter(entry => 
                !hideZeroEntries || parseFloat(entry.amount) !== 0
            );
        }
    };

    const processEntries = (entries) => {
        if (!groupEntries) return entries;

        if (groupedByType) {
            // If entries are grouped by type, process each group
            const processedGroups = {};
            Object.entries(entries).forEach(([type, typeEntries]) => {
                const entriesArray = Array.isArray(typeEntries) ? typeEntries : [typeEntries];
                
                // First, separate entries with empty descriptions
                const emptyDescEntries = entriesArray.filter(entry => !entry.description?.trim());
                const validEntries = entriesArray.filter(entry => entry.description?.trim());

                const groupedData = {};
                validEntries.forEach((entry) => {
                    const key = getEntriesKey(entry);
                    if (!groupedData[key]) {
                        groupedData[key] = {
                            description: entry.description,
                            recipient: entry.recipient,
                            amount: entry.amount,
                            currency: entry.currency,
                            count: 1,
                            children: [entry],
                            isGroup: true,
                            groupId: key
                        };
                    } else {
                        groupedData[key].count += 1;
                        groupedData[key].amount = (parseFloat(groupedData[key].amount) + parseFloat(entry.amount)).toString();
                        groupedData[key].children.push(entry);
                    }
                });

                // Get grouped entries that appear more than once
                const groupedEntries = Object.values(groupedData)
                    .filter(group => group.count > 1);

                // Get entries that only appear once
                const singleEntries = validEntries.filter(entry => 
                    !groupedEntries.some(group => 
                        group.children.some(child => getEntriesKey(child) === getEntriesKey(entry))
                    )
                );

                // Combine grouped entries, single entries, and empty description entries
                processedGroups[type] = [...groupedEntries, ...singleEntries, ...emptyDescEntries];
            });
            return processedGroups;
        } else {
            // If entries are not grouped by type, process directly
            const entriesArray = Array.isArray(entries) ? entries : Object.values(entries).flat();

            // First, separate entries with empty descriptions
            const emptyDescEntries = entriesArray.filter(entry => !entry.description?.trim());
            const validEntries = entriesArray.filter(entry => entry.description?.trim());

            const groupedData = {};
            validEntries.forEach((entry) => {
                const key = getEntriesKey(entry);
                if (!groupedData[key]) {
                    groupedData[key] = {
                        description: entry.description,
                        recipient: entry.recipient,
                        amount: entry.amount,
                        currency: entry.currency,
                        count: 1,
                        children: [entry],
                        isGroup: true,
                        groupId: key
                    };
                } else {
                    groupedData[key].count += 1;
                    groupedData[key].amount = (parseFloat(groupedData[key].amount) + parseFloat(entry.amount)).toString();
                    groupedData[key].children.push(entry);
                }
            });

            // Get grouped entries that appear more than once
            const groupedEntries = Object.values(groupedData)
                .filter(group => group.count > 1);

            // Get entries that only appear once
            const singleEntries = validEntries.filter(entry => 
                !groupedEntries.some(group => 
                    group.children.some(child => getEntriesKey(child) === getEntriesKey(entry))
                )
            );

            // Combine grouped entries, single entries, and empty description entries
            return [...groupedEntries, ...singleEntries, ...emptyDescEntries];
        }
    };

    const calculateTotalAmount = (entries) => {
        if (!entries) return 0;
        
        if (groupedByType) {
            return Object.values(entries).reduce((total, typeEntries) => {
                return total + typeEntries.reduce((typeTotal, entry) => {
                    if (!isEntryExcluded(entry)) {
                        return typeTotal + parseFloat(entry.amount);
                    }
                    return typeTotal;
                }, 0);
            }, 0);
        } else {
            return entries.reduce((total, entry) => {
                if (!isEntryExcluded(entry)) {
                    return total + parseFloat(entry.amount);
                }
                return total;
            }, 0);
        }
    };

    const renderEntry = (entry, index) => (
        <div key={index} className="flex items-center justify-between p-3 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
            <div className="flex-1">
                <div className="flex items-center space-x-2">
                    {entry.isGroup && (
                        <button
                            onClick={() => toggleGroup(entry.groupId)}
                            className="text-gray-500 hover:text-gray-700"
                        >
                            {expandedGroups.has(entry.groupId) ? '▼' : '▶'}
                        </button>
                    )}
                    <div className="flex-1">
                        <div className="flex items-center space-x-2">
                            <span className="font-medium">{entry.description}</span>
                            {entry.recipient && (
                                <span className="text-sm text-gray-500">
                                    → {entry.recipient}
                                </span>
                            )}
                            {/* Display categories for both single and grouped entries */}
                            <div className="flex gap-1">
                                {(entry.isGroup ? entry.children[0].categories : entry.categories)?.map((category, catIndex) => (
                                    <span
                                        key={catIndex}
                                        className="px-2 py-0.5 text-xs rounded-full text-white"
                                        style={{ backgroundColor: category.color }}
                                    >
                                        {category.name}
                                    </span>
                                ))}
                            </div>
                        </div>
                        {entry.isGroup && (
                            <div className="text-sm text-gray-500">
                                {entry.count} similar entries
                            </div>
                        )}
                    </div>
                </div>
                {entry.isGroup && expandedGroups.has(entry.groupId) && (
                    <div className="mt-2 pl-6 space-y-2">
                        {entry.children.map((child, childIndex) => (
                            <div key={childIndex} className="flex justify-between items-center text-sm text-gray-600">
                                <div className="flex items-center space-x-2">
                                    <span>{child.description}</span>
                                    {child.recipient && (
                                        <span className="text-gray-500">
                                            → {child.recipient}
                                        </span>
                                    )}
                                    {/* Display categories for child entries */}
                                    <div className="flex gap-1">
                                        {child.categories?.map((category, catIndex) => (
                                            <span
                                                key={catIndex}
                                                className="px-2 py-0.5 text-xs rounded-full text-white"
                                                style={{ backgroundColor: category.color }}
                                            >
                                                {category.name}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                                <span>{formatAmount(child.amount, child.currency)}</span>
                            </div>
                        ))}
                    </div>
                )}
            </div>
            <div className="flex items-center space-x-4">
                <span className={`font-medium ${parseFloat(entry.amount) < 0 ? 'text-red-600' : 'text-green-600'}`}>
                    {formatAmount(entry.amount, entry.currency)}
                </span>
                <div className="flex items-center space-x-2">
                    <select
                        value=""
                        onChange={(e) => assignCategory(entry, parseInt(e.target.value))}
                        className="rounded-md border-gray-300 text-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        disabled={assigningCategory}
                    >
                        <option value="">Set category</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>
                    <button
                        onClick={() => toggleExcludeEntry(entry)}
                        className={`p-1 rounded ${
                            isEntryExcluded(entry)
                                ? 'bg-red-100 text-red-600 hover:bg-red-200'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        }`}
                    >
                        {isEntryExcluded(entry) ? 'Excluded' : 'Exclude'}
                    </button>
                </div>
            </div>
        </div>
    );

    const renderGroupedEntry = (entry, index) => (
        <div key={index} className="mb-4">
            {renderEntry(entry, index)}
            {entry.isGroup && expandedGroups.has(entry.groupId) && (
                <div className="ml-8 mt-2 space-y-2">
                    {entry.children.map((child, childIndex) => (
                        <div key={childIndex} className="border-l-2 border-gray-200 pl-4">
                            {renderEntry(child, `${index}-${childIndex}`)}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );

    const renderEntries = () => {
        const processedEntries = processEntries(entries);
        
        if (groupedByType) {
            return Object.entries(processedEntries).map(([type, typeEntries]) => (
                <div key={type} className="mb-8">
                    <h3 className="text-lg font-medium text-gray-900 mb-4">{type}</h3>
                    <div className="space-y-4">
                        {typeEntries.map((entry, index) => renderEntry(entry, index))}
                    </div>
                </div>
            ));
        } else {
            // If not grouped by type, render entries directly
            const entriesArray = Array.isArray(processedEntries) ? processedEntries : [processedEntries];
            return (
                <div className="space-y-4">
                    {entriesArray.map((entry, index) => renderEntry(entry, index))}
                </div>
            );
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center mb-4">
                    <div className="flex items-center space-x-4">
                        <h2 className="text-lg font-medium text-gray-900">
                            CSV File Entries
                        </h2>
                        <Link
                            href={route('csv.map', csvFileId)}
                            className="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
                        >
                            Edit Mapping
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="View CSV" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="lg:flex lg:gap-6">
                        {/* Main content */}
                        <div className="flex-1">
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    {/* Mobile category summary */}
                                    <div className="lg:hidden">
                                        <CategorySummary 
                                            entries={groupEntries ? processEntries(filterEntries(entries)) : filterEntries(entries)}
                                            categories={categories}
                                            isEntryExcluded={isEntryExcluded}
                                            formatAmount={formatAmount}
                                        />
                                    </div>

                                    {/* Controls */}
                                    <div className="flex flex-col sm:flex-row gap-4 mb-6">
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                id="hideZeroEntries"
                                                checked={hideZeroEntries}
                                                onChange={(e) => setHideZeroEntries(e.target.checked)}
                                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                            <label htmlFor="hideZeroEntries">Hide zero entries</label>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                id="groupEntries"
                                                checked={groupEntries}
                                                onChange={(e) => setGroupEntries(e.target.checked)}
                                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                            />
                                            <label htmlFor="groupEntries">Group similar entries</label>
                                        </div>
                                    </div>

                                    {/* Total Amount */}
                                    <div className="mb-6 bg-gray-100 p-4 rounded-lg">
                                        <div className="flex justify-between items-center">
                                            <span className="text-lg font-semibold">Total Amount</span>
                                            <span className="text-xl font-bold">
                                                {formatAmount(calculateTotalAmount(filterEntries(entries)), 'PLN')}
                                            </span>
                                        </div>
                                    </div>

                                    {/* Entries list */}
                                    <div className="space-y-4">
                                        {renderEntries()}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Desktop category summary sidebar */}
                        <div className="hidden lg:block lg:w-80">
                            <CategorySummary 
                                entries={groupEntries ? processEntries(filterEntries(entries)) : filterEntries(entries)}
                                categories={categories}
                                isEntryExcluded={isEntryExcluded}
                                formatAmount={formatAmount}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
