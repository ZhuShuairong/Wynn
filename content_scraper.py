# content_scraper.py

import os
import re
import csv
import logging
from concurrent.futures import ThreadPoolExecutor, as_completed
from tqdm import tqdm
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.common.exceptions import WebDriverException, TimeoutException
# Import selenium-stealth
from selenium_stealth import stealth

import config

def _sanitize_filename(link):
    """Sanitize a link to create a valid filename."""
    # Remove scheme (http/https) if present
    sanitized = re.sub(r'^https?://', '', link)
    # Replace invalid filename characters with underscore
    sanitized = re.sub(r'[\\/:*?"<>|]+', '_', sanitized)
    # Limit length if necessary (optional)
    max_len = 200
    if len(sanitized) > max_len:
        sanitized = sanitized[:max_len]
    return sanitized

def _extract_text_from_link(link):
    """
    Extract full page text from a link using headless Chrome with stealth mode.
    Returns the text content or None if an error occurs.
    """
    options = webdriver.ChromeOptions()
    options.add_argument('--headless')
    options.add_argument('--disable-blink-features=AutomationControlled')
    options.add_argument('--disable-infobars')
    options.add_argument('--disable-extensions')
    options.add_argument('--disable-gpu')
    options.add_argument('--no-sandbox')
    options.add_argument('--ignore-certificate-errors')
    options.add_argument('--disable-dev-shm-usage')
    # options.add_argument('--enable-unsafe-swiftshader') # Maybe not needed

    driver = None
    try:
        driver = webdriver.Chrome(options=options)
        # Apply stealth settings
        stealth(driver,
                languages=["en-US", "en"],
                vendor="Google Inc.",
                platform="Win32",
                webgl_vendor="Intel Inc.",
                renderer="Intel Iris OpenGL Engine",
                fix_hairline=True,
                run_on_insecure_origins=True, # Be cautious with this one
                user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36" # Keep UA updated
               )

        driver.get(link)
        # Wait for page to be reasonably loaded (adjust timeout as needed)
        WebDriverWait(driver, 20).until(
            lambda d: d.execute_script('return document.readyState') == 'complete'
        )
        # Find the body element and get its text
        body = driver.find_element(By.TAG_NAME, "body")
        page_text = body.text.strip()
        return page_text

    except TimeoutException:
        logging.warning(f"Timeout waiting for page load complete for {link}")
        return None # Or return partially loaded text if available
    except WebDriverException as e:
        # Log specific WebDriver errors
        logging.error(f"WebDriver error processing {link}: {str(e)}")
        return None
    except Exception as e:
        # Catch other potential errors (e.g., finding body element)
        logging.error(f"Unexpected error extracting text from {link}: {str(e)}")
        return None
    finally:
        if driver:
            driver.quit()

def _process_single_link(link, output_dir):
    """
    Process a single link: extract its text and save it to a file in output_dir.
    Returns a tuple (link, status_message).
    """
    text = _extract_text_from_link(link)
    if text:
        # Sanitize link for filename
        safe_filename = _sanitize_filename(link) + ".txt"
        filepath = os.path.join(output_dir, safe_filename)
        try:
            # Use 'w' mode to overwrite any existing file
            with open(filepath, 'w', encoding='utf-8') as f:
                # Store URL at the beginning for reference
                f.write(f"Original URL: {link}\n")
                f.write("-" * 50 + "\n") # Separator
                f.write("Content:\n")
                f.write(text)
            return (link, 'success')
        except IOError as e:
            logging.error(f"File writing error for {link} to {filepath}: {str(e)}")
            return (link, f'file error: {str(e)}')
        except Exception as e:
             logging.error(f"Unexpected error saving file for {link}: {str(e)}")
             return (link, f'unexpected file save error: {str(e)}')
    else:
        # Text extraction failed (error already logged in _extract_text_from_link)
        return (link, 'extraction failed')

def scrape_all_websites_content():
    """
    Scrapes content from all websites listed in the filtered links CSV.
    Uses ThreadPoolExecutor for parallel processing.
    """
    input_filename = config.FILTERED_LINKS_CSV
    output_dir = config.EXTRACTED_CONTENT_DIR
    max_workers = config.MAX_WORKERS_CONTENT_SCRAPE

    logging.info(f"Starting content scraping from links in {input_filename}...")
    if not os.path.exists(input_filename):
        logging.error(f"Input file not found: {input_filename}. Cannot scrape content.")
        return

    links_to_scrape = []
    try:
        with open(input_filename, "r", encoding='utf-8') as f:
            reader = csv.reader(f)
            header = next(reader) # Skip header
            links_to_scrape = [row[0] for row in reader if row] # Ensure row is not empty
    except FileNotFoundError:
         logging.error(f"Filtered links file {input_filename} not found during scraping.")
         return
    except StopIteration:
         logging.warning(f"Filtered links file {input_filename} is empty.")
         return
    except Exception as e:
        logging.error(f"Error reading filtered links file {input_filename}: {e}")
        return

    if not links_to_scrape:
        logging.warning("No links found in the filtered file to scrape.")
        return

    os.makedirs(output_dir, exist_ok=True)
    logging.info(f"Saving extracted content to: {output_dir}")
    logging.info(f"Using up to {max_workers} workers for scraping.")

    success_count = 0
    error_count = 0

    # Use ThreadPoolExecutor for concurrent scraping
    with ThreadPoolExecutor(max_workers=max_workers) as executor:
        # Create future tasks
        future_to_link = {executor.submit(_process_single_link, link, output_dir): link for link in links_to_scrape}

        # Process completed futures using tqdm for progress bar
        with tqdm(total=len(links_to_scrape), desc="Scraping Web Content", unit="page") as pbar:
            for future in as_completed(future_to_link):
                link = future_to_link[future]
                try:
                    result_link, status = future.result()
                    if status == 'success':
                        success_count += 1
                    else:
                        error_count += 1
                        # Error logged within _process_single_link or _extract_text_from_link
                        logging.warning(f"Failed {result_link}: {status}")
                except Exception as exc:
                    # Catch errors from the future execution itself
                    error_count += 1
                    logging.error(f"Task for link {link} generated an exception: {exc}")

                # Update progress bar
                pbar.update(1)
                pbar.set_postfix_str(f"Success: {success_count}, Errors: {error_count}")

    logging.info(f"Content scraping completed. Successful: {success_count}, Failed: {error_count}")

# --- Add this block to make the script runnable ---
if __name__ == "__main__":
    import logging
    import os
    logging.basicConfig(
        level=logging.INFO,
        format='%(asctime)s - %(levelname)-8s - %(name)-12s - %(message)s',
        datefmt='%Y-%m-%d %H:%M:%S'
    )
    import config

    logging.info("Running Content Scraper module directly...")
    if not os.path.exists(config.FILTERED_LINKS_CSV):
         logging.error(f"Input file {config.FILTERED_LINKS_CSV} not found. Cannot run Content Scraper.")
    else:
        scrape_all_websites_content()
    logging.info("Content Scraper module execution finished.")
# --- End of added block ---