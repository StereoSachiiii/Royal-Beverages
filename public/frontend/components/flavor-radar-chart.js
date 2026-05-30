/**
 * Flavor Radar Chart Component
 * Interactive 6-axis visualization for flavor profiles
 * Pure SVG - no external libraries
 */

export class FlavorRadarChart {
    constructor(container, options = {}) {
        this.container = typeof container === 'string'
            ? document.querySelector(container)
            : container;

        this.options = {
            size: options.size || 200,
            maxValue: options.maxValue || 10,
            levels: options.levels || 5,
            interactive: options.interactive || false,
            showLabels: options.showLabels !== false,
            showValues: options.showValues !== false,
            colors: {
                grid: options.colors?.grid || 'rgba(0,0,0,0.1)',
                axes: options.colors?.axes || 'rgba(0,0,0,0.2)',
                area: options.colors?.area || 'rgba(212, 175, 55, 0.3)',
                stroke: options.colors?.stroke || '#d4af37',
                points: options.colors?.points || '#d4af37',
                labels: options.colors?.labels || '#333',
                values: options.colors?.values || '#666',
                ...options.colors
            },
            onChange: options.onChange || null
        };

        this.axes = [
            { key: 'sweetness', label: 'Sweetness', icon: '🍯' },
            { key: 'bitterness', label: 'Bitterness', icon: '🍋' },
            { key: 'strength', label: 'Strength', icon: '💪' },
            { key: 'smokiness', label: 'Smokiness', icon: '🔥' },
            { key: 'fruitiness', label: 'Fruitiness', icon: '🍇' },
            { key: 'spiciness', label: 'Spiciness', icon: '🌶️' }
        ];

        this.data = {};
        this.dragging = null;

        if (this.container) {
            this.init();
        }
    }

    init() {
        this.container.innerHTML = '';
        this.container.classList.add('flavor-radar-container');

        const size = this.options.size;
        const padding = this.options.showLabels ? 50 : 20;
        const fullSize = size + padding * 2;

        this.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.svg.setAttribute('width', fullSize);
        this.svg.setAttribute('height', fullSize);
        this.svg.setAttribute('viewBox', `0 0 ${fullSize} ${fullSize}`);
        this.svg.classList.add('flavor-radar-svg');

        // Create groups
        this.gridGroup = this.createGroup('radar-grid');
        this.axesGroup = this.createGroup('radar-axes');
        this.dataGroup = this.createGroup('radar-data');
        this.labelsGroup = this.createGroup('radar-labels');
        this.pointsGroup = this.createGroup('radar-points');

        this.centerX = fullSize / 2;
        this.centerY = fullSize / 2;
        this.radius = size / 2;

        this.drawGrid();
        this.drawAxes();

        this.container.appendChild(this.svg);

        if (this.options.interactive) {
            this.setupInteraction();
        }
    }

    createGroup(className) {
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.classList.add(className);
        this.svg.appendChild(g);
        return g;
    }

    drawGrid() {
        const { levels, colors } = this.options;

        for (let i = 1; i <= levels; i++) {
            const r = (this.radius / levels) * i;
            const points = this.axes.map((_, j) => {
                const angle = (Math.PI * 2 / this.axes.length) * j - Math.PI / 2;
                return `${this.centerX + r * Math.cos(angle)},${this.centerY + r * Math.sin(angle)}`;
            }).join(' ');

            const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
            polygon.setAttribute('points', points);
            polygon.setAttribute('fill', 'none');
            polygon.setAttribute('stroke', colors.grid);
            polygon.setAttribute('stroke-width', '1');
            this.gridGroup.appendChild(polygon);
        }
    }

    drawAxes() {
        const { colors, showLabels } = this.options;

        this.axes.forEach((axis, i) => {
            const angle = (Math.PI * 2 / this.axes.length) * i - Math.PI / 2;
            const x2 = this.centerX + this.radius * Math.cos(angle);
            const y2 = this.centerY + this.radius * Math.sin(angle);

            // Axis line
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('x1', this.centerX);
            line.setAttribute('y1', this.centerY);
            line.setAttribute('x2', x2);
            line.setAttribute('y2', y2);
            line.setAttribute('stroke', colors.axes);
            line.setAttribute('stroke-width', '1');
            this.axesGroup.appendChild(line);

            // Label
            if (showLabels) {
                const labelRadius = this.radius + 25;
                const labelX = this.centerX + labelRadius * Math.cos(angle);
                const labelY = this.centerY + labelRadius * Math.sin(angle);

                const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', labelX);
                text.setAttribute('y', labelY);
                text.setAttribute('text-anchor', 'middle');
                text.setAttribute('dominant-baseline', 'middle');
                text.setAttribute('fill', colors.labels);
                text.setAttribute('font-size', '11');
                text.setAttribute('font-weight', '600');
                text.textContent = axis.label;
                this.labelsGroup.appendChild(text);
            }
        });
    }

    setData(flavorProfile) {
        this.data = { ...flavorProfile };
        this.render();
    }

    render() {
        // Clear previous data
        this.dataGroup.innerHTML = '';
        this.pointsGroup.innerHTML = '';

        const { colors, maxValue, showValues, interactive } = this.options;

        // Calculate points
        const points = this.axes.map((axis, i) => {
            const value = this.data[axis.key] || 0;
            const normalizedValue = value / maxValue;
            const angle = (Math.PI * 2 / this.axes.length) * i - Math.PI / 2;
            const r = this.radius * normalizedValue;
            return {
                x: this.centerX + r * Math.cos(angle),
                y: this.centerY + r * Math.sin(angle),
                value,
                axis,
                angle
            };
        });

        // Draw filled area
        const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        polygon.setAttribute('points', points.map(p => `${p.x},${p.y}`).join(' '));
        polygon.setAttribute('fill', colors.area);
        polygon.setAttribute('stroke', colors.stroke);
        polygon.setAttribute('stroke-width', '2');
        polygon.classList.add('radar-area');
        this.dataGroup.appendChild(polygon);

        // Draw points
        points.forEach((p, i) => {
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', p.x);
            circle.setAttribute('cy', p.y);
            circle.setAttribute('r', interactive ? 8 : 4);
            circle.setAttribute('fill', colors.points);
            circle.setAttribute('stroke', '#fff');
            circle.setAttribute('stroke-width', '2');
            circle.classList.add('radar-point');
            circle.dataset.axis = p.axis.key;

            if (interactive) {
                circle.style.cursor = 'pointer';
            }

            this.pointsGroup.appendChild(circle);

            // Value label
            if (showValues) {
                const valueRadius = this.radius * (p.value / maxValue) + 15;
                const valueX = this.centerX + valueRadius * Math.cos(p.angle);
                const valueY = this.centerY + valueRadius * Math.sin(p.angle);

                const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                text.setAttribute('x', valueX);
                text.setAttribute('y', valueY);
                text.setAttribute('text-anchor', 'middle');
                text.setAttribute('dominant-baseline', 'middle');
                text.setAttribute('fill', colors.values);
                text.setAttribute('font-size', '10');
                text.setAttribute('font-weight', '600');
                text.textContent = p.value;
                this.pointsGroup.appendChild(text);
            }
        });
    }

    setupInteraction() {
        const svg = this.svg;

        svg.addEventListener('mousedown', (e) => this.onDragStart(e));
        svg.addEventListener('mousemove', (e) => this.onDragMove(e));
        svg.addEventListener('mouseup', () => this.onDragEnd());
        svg.addEventListener('mouseleave', () => this.onDragEnd());

        // Touch support
        svg.addEventListener('touchstart', (e) => this.onDragStart(e));
        svg.addEventListener('touchmove', (e) => this.onDragMove(e));
        svg.addEventListener('touchend', () => this.onDragEnd());
    }

    onDragStart(e) {
        const point = e.target.closest('.radar-point');
        if (point) {
            this.dragging = point.dataset.axis;
            e.preventDefault();
        }
    }

    onDragMove(e) {
        if (!this.dragging) return;

        const rect = this.svg.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;

        const x = clientX - rect.left - this.centerX;
        const y = clientY - rect.top - this.centerY;

        const distance = Math.sqrt(x * x + y * y);
        const normalizedValue = Math.min(1, Math.max(0, distance / this.radius));
        const value = Math.round(normalizedValue * this.options.maxValue);

        this.data[this.dragging] = value;
        this.render();

        if (this.options.onChange) {
            this.options.onChange(this.data);
        }
    }

    onDragEnd() {
        this.dragging = null;
    }

    getData() {
        return { ...this.data };
    }

    destroy() {
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

// CSS Styles (injected)
const styles = `
.flavor-radar-container {
    display: inline-block;
}

.flavor-radar-svg {
    overflow: visible;
}

.radar-area {
    transition: all 0.3s ease;
}

.radar-point {
    transition: all 0.2s ease;
}

.radar-point:hover {
    r: 10;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}
`;

// Inject styles once
if (!document.getElementById('flavor-radar-styles')) {
    const styleEl = document.createElement('style');
    styleEl.id = 'flavor-radar-styles';
    styleEl.textContent = styles;
    document.head.appendChild(styleEl);
}

export default FlavorRadarChart;
