/**
 * Console Logger - A utility for displaying console logs in the UI
 * Helps with debugging on mobile devices or where developer tools aren't easily accessible
 */
(function() {
    // Create UI elements for the logger
    const createLogger = function() {
        const loggerContainer = document.createElement('div');
        loggerContainer.id = 'console-logger';
        loggerContainer.style.cssText = 'position: fixed; bottom: 10px; right: 10px; width: 50px; height: 50px; ' +
            'background-color: rgba(0, 0, 0, 0.7); color: white; z-index: 9999; border-radius: 50%; ' + 
            'display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 24px;';
        loggerContainer.innerHTML = '🪵';
        
        const logPanel = document.createElement('div');
        logPanel.id = 'console-log-panel';
        logPanel.style.cssText = 'position: fixed; bottom: 70px; right: 10px; width: 380px; height: 400px; ' +
            'background-color: rgba(0, 0, 0, 0.9); color: white; z-index: 9998; border-radius: 5px; ' + 
            'padding: 10px; font-family: monospace; font-size: 12px; overflow-y: auto; display: none;';
        
        document.body.appendChild(loggerContainer);
        document.body.appendChild(logPanel);
        
        // Toggle log panel visibility when clicking the logger button
        loggerContainer.addEventListener('click', function() {
            const panel = document.getElementById('console-log-panel');
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });
        
        // Clear button
        const clearButton = document.createElement('button');
        clearButton.style.cssText = 'position: absolute; top: 5px; right: 5px; background: #666; ' +
            'color: white; border: none; border-radius: 3px; padding: 2px 5px; cursor: pointer;';
        clearButton.textContent = 'Clear';
        clearButton.addEventListener('click', function(e) {
            e.stopPropagation();
            logPanel.innerHTML = '';
            logPanel.appendChild(clearButton);
        });
        
        logPanel.appendChild(clearButton);
    };
    
    // Override console methods to capture logs
    const setupConsoleOverride = function() {
        const originalConsole = {
            log: console.log,
            error: console.error,
            warn: console.warn,
            info: console.info
        };
        
        // Helper to add message to the log panel
        const addToLogPanel = function(type, args) {
            const panel = document.getElementById('console-log-panel');
            if (!panel) return;
            
            const logEntry = document.createElement('div');
            logEntry.style.marginBottom = '5px';
            logEntry.style.borderBottom = '1px solid #333';
            logEntry.style.paddingBottom = '5px';
            
            // Add timestamp
            const time = new Date().toTimeString().split(' ')[0];
            logEntry.innerHTML = `<span style="color: #888;">[${time}]</span> `;
            
            // Add type indicator with color
            let typeColor = '#fff';
            switch (type) {
                case 'error': typeColor = '#ff5252'; break;
                case 'warn': typeColor = '#ffbc52'; break;
                case 'info': typeColor = '#52c0ff'; break;
                case 'network': typeColor = '#52ff8d'; break;
                default: typeColor = '#fff';
            }
            
            logEntry.innerHTML += `<span style="color: ${typeColor};">[${type}]</span> `;
            
            // Format the log content
            const formattedContent = Array.from(args).map(arg => {
                if (typeof arg === 'object') {
                    try {
                        return JSON.stringify(arg);
                    } catch (e) {
                        return String(arg);
                    }
                }
                return String(arg);
            }).join(' ');
            
            logEntry.innerHTML += formattedContent;
            panel.appendChild(logEntry);
            
            // Auto-scroll to bottom
            panel.scrollTop = panel.scrollHeight;
        };
        
        // Override console methods
        console.log = function() {
            addToLogPanel('log', arguments);
            originalConsole.log.apply(console, arguments);
        };
        
        console.error = function() {
            addToLogPanel('error', arguments);
            originalConsole.error.apply(console, arguments);
        };
        
        console.warn = function() {
            addToLogPanel('warn', arguments);
            originalConsole.warn.apply(console, arguments);
        };
        
        console.info = function() {
            addToLogPanel('info', arguments);
            originalConsole.info.apply(console, arguments);
        };
        
        // Add network logger
        console.network = function() {
            addToLogPanel('network', arguments);
            originalConsole.log.apply(console, arguments);
        };
    };
    
    // Monitor AJAX/Fetch requests
    const setupNetworkMonitoring = function() {
        // Override fetch API
        const originalFetch = window.fetch;
        window.fetch = function() {
            const url = arguments[0];
            const options = arguments[1] || {};
            
            console.network(`Fetch request to: ${url}`);
            if (options.method) {
                console.network(`Method: ${options.method}`);
            }
            
            const startTime = new Date().getTime();
            
            return originalFetch.apply(this, arguments)
                .then(response => {
                    const duration = new Date().getTime() - startTime;
                    console.network(`Response from ${url} - Status: ${response.status}, Time: ${duration}ms`);
                    
                    // Clone the response to inspect its content
                    const clonedResponse = response.clone();
                    
                    // Try to parse response based on content type
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        clonedResponse.json().then(data => {
                            console.network('Response JSON data:', data);
                        }).catch(err => {
                            console.network('Failed to parse JSON response:', err.message);
                            
                            // Try to get text if JSON parsing fails
                            clonedResponse.text().then(text => {
                                console.network('Response text:', text);
                            }).catch(() => {});
                        });
                    } else if (contentType && contentType.includes('text/')) {
                        clonedResponse.text().then(text => {
                            console.network('Response text:', text);
                        }).catch(() => {});
                    }
                    
                    return response;
                })
                .catch(error => {
                    const duration = new Date().getTime() - startTime;
                    console.network(`Error fetching ${url} - Time: ${duration}ms, Error: ${error.message}`);
                    throw error;
                });
        };
        
        // Override XMLHttpRequest
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;
        
        XMLHttpRequest.prototype.open = function() {
            this._url = arguments[1];
            this._method = arguments[0];
            originalXHROpen.apply(this, arguments);
        };
        
        XMLHttpRequest.prototype.send = function() {
            console.network(`XHR request to: ${this._url}, Method: ${this._method}`);
            
            const startTime = new Date().getTime();
            
            this.addEventListener('load', function() {
                const duration = new Date().getTime() - startTime;
                console.network(`XHR response from ${this._url} - Status: ${this.status}, Time: ${duration}ms`);
                
                try {
                    const contentType = this.getResponseHeader('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const jsonData = JSON.parse(this.responseText);
                        console.network('XHR Response JSON data:', jsonData);
                    } else {
                        console.network('XHR Response text:', this.responseText);
                    }
                } catch (e) {
                    console.network('XHR Response text:', this.responseText);
                }
            });
            
            this.addEventListener('error', function() {
                const duration = new Date().getTime() - startTime;
                console.network(`XHR error from ${this._url} - Time: ${duration}ms`);
            });
            
            originalXHRSend.apply(this, arguments);
        };
    };
    
    // Initialize the logger when the DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            createLogger();
            setupConsoleOverride();
            setupNetworkMonitoring();
            console.info('Console Logger initialized with network monitoring');
        });
    } else {
        createLogger();
        setupConsoleOverride();
        setupNetworkMonitoring();
        console.info('Console Logger initialized with network monitoring');
    }
})(); 
 
 
 
 
 
 