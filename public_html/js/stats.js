function numberWithCommas(x) {
    return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function createBarChart(svg, data) {
    const margin = 80;
    const width = 940 - 2 * margin;
    const height = 420 - 2 * margin;

    const chart = svg.append('g').attr('transform', `translate(${margin}, ${margin})`);
    const yScale = d3.scaleLinear()
        .range([height, 0])
        .domain([0, Math.max(...data.map(elem => elem.value))]);
    chart.append('g')
        .attr('class', 'chart-grid')
        .call(d3.axisLeft().scale(yScale).tickSize(-width, 0, 0).tickFormat(''));
    chart.append('g').call(d3.axisLeft(yScale));
    const xScale = d3.scaleBand()
        .range([0, width])
        .domain(data.map(elem => elem.label))
        .padding(0.2);
    chart.append('g')
        .attr('transform', `translate(0, ${height})`)
        .call(d3.axisBottom(xScale));
    chart.selectAll().data(data).enter()
        .append('rect')
        .attr('id', (s) => 'by-year-chart-bar-' + s.label)
        .attr('x', (s) => xScale(s.label))
        .attr('y', (s) => yScale(s.value))
        .attr('height', (s) => height - yScale(s.value))
        .attr('width', xScale.bandwidth())
        .on('mouseenter', function () {
            const textNodeId = 'by-year-chart-text-' + this.id.replace('by-year-chart-bar-', '');
            document.getElementById(textNodeId).style.opacity = 1;
        })
        .on('mouseleave', function () {
            const textNodeId = 'by-year-chart-text-' + this.id.replace('by-year-chart-bar-', '');
            document.getElementById(textNodeId).style.opacity = 0;
        });

    svg.append('text')
        .attr('x', -(height / 2) - margin)
        .attr('y', margin / 3)
        .attr('transform', 'rotate(-90)')
        .attr('text-anchor', 'middle')
        .text('Number of Samples');

    svg.append('text')
        .attr('x', width / 2 + margin)
        .attr('y', height + margin * 1.5)
        .attr('text-anchor', 'middle')
        .text('Year');

    svg.append('text')
        .attr('x', width / 2 + margin)
        .attr('y', 40)
        .attr('text-anchor', 'middle')
        .text('Uploaded Sample by Year');

    chart.selectAll()
        .data(data)
        .enter()
        .append('text')
        .attr('id', (s) => 'by-year-chart-text-' + s.label)
        .attr('x', (s) => xScale(s.label) + xScale.bandwidth() / 2)
        .attr('y', (s) => yScale(s.value / 2) + 5)
        .style('fill', 'white')
        .style('opacity', 0)
        .attr('text-anchor', 'middle')
        .text((s) => numberWithCommas(s.value))
        .on('mouseenter', function () {
            this.style.opacity = 1;
        });
}
