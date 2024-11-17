import React from 'react';

const CategorySummary = ({ entries, categories, isEntryExcluded, formatAmount }) => {
    const calculateCategorySums = () => {
        const sums = new Map();
        let uncategorizedSum = 0;

        // Initialize sums for all categories
        categories.forEach(category => {
            sums.set(category.id, { amount: 0, name: category.name, color: category.color });
        });

        // Convert entries to array if it's an object (grouped by type)
        const entriesArray = Array.isArray(entries) ? entries : Object.values(entries).flat();

        // Process entries
        const processEntry = (entry) => {
            if (isEntryExcluded(entry)) return;

            const amount = parseFloat(entry.amount);
            
            if (entry.isGroup) {
                entry.children.forEach(child => {
                    if (!isEntryExcluded({ id: child.id })) {
                        if (child.categories?.length > 0) {
                            child.categories.forEach(category => {
                                const currentSum = sums.get(category.id);
                                if (currentSum) {
                                    sums.set(category.id, {
                                        ...currentSum,
                                        amount: currentSum.amount + amount / entry.children.length
                                    });
                                }
                            });
                        } else {
                            uncategorizedSum += amount / entry.children.length;
                        }
                    }
                });
            } else {
                if (entry.categories?.length > 0) {
                    entry.categories.forEach(category => {
                        const currentSum = sums.get(category.id);
                        if (currentSum) {
                            sums.set(category.id, {
                                ...currentSum,
                                amount: currentSum.amount + amount
                            });
                        }
                    });
                } else {
                    uncategorizedSum += amount;
                }
            }
        };

        // Process all entries
        entriesArray.forEach(processEntry);

        // Convert sums to array and add uncategorized
        const sumsArray = Array.from(sums.values())
            .filter(sum => sum.amount !== 0)
            .sort((a, b) => Math.abs(b.amount) - Math.abs(a.amount));

        if (uncategorizedSum !== 0) {
            sumsArray.push({
                name: 'Uncategorized',
                amount: uncategorizedSum,
                color: '#9E9E9E'
            });
        }

        return sumsArray;
    };

    const categorySums = calculateCategorySums();
    const totalAmount = categorySums.reduce((sum, category) => sum + category.amount, 0);

    return (
        <div className="bg-white rounded-lg shadow p-4 mb-4 lg:mb-0">
            <h2 className="text-lg font-semibold mb-4">Category Summary</h2>
            <div className="space-y-3">
                {categorySums.map((category, index) => (
                    <div key={index} className="flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <div 
                                className="w-3 h-3 rounded-full" 
                                style={{ backgroundColor: category.color }}
                            />
                            <span>{category.name}</span>
                        </div>
                        <span className="font-medium">
                            {formatAmount(category.amount)}
                        </span>
                    </div>
                ))}
                <div className="border-t pt-2 mt-2">
                    <div className="flex items-center justify-between font-semibold">
                        <span>Total</span>
                        <span>{formatAmount(totalAmount)}</span>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default CategorySummary;
