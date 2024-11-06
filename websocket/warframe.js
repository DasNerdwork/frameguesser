import Items from 'warframe-items';
import fs from 'fs';
import path from 'path';
import { exec } from 'child_process';
import pkg from 'follow-redirects'; // Import the whole package
const { https } = pkg; // Destructure the https module

// Create an instance of Items with the category 'Warframes'
const items = new Items({ category: ['Warframes'] });

// Filter out Bonewidow and Voidrig
const warframes = items.filter(warframe => 
    warframe.name !== 'Bonewidow' && warframe.name !== 'Voidrig'
);

// Function to download an image and convert it to webp
const downloadAndConvertToWebp = (url, outputPath) => {
    return new Promise((resolve, reject) => {
        const file = fs.createWriteStream(outputPath);
        https.get(url, response => {
            // Check if the response status is OK (200)
            if (response.statusCode !== 200) {
                fs.unlink(outputPath, () => {}); // Delete the file if it was created
                return reject(`Failed to get '${url}' (${response.statusCode})`);
            }

            response.pipe(file);
            file.on('finish', () => {
                file.close(() => {
                    // Convert to webp using cwebp
                    exec(`cwebp ${outputPath} -o ${outputPath.replace(/\.\w+$/, '.webp')}`, (err) => {
                        if (err) {
                            reject(`Error converting to webp: ${err}`);
                        } else {
                            // Remove the original image file
                            fs.unlink(outputPath, (unlinkErr) => {
                                if (unlinkErr) {
                                    console.error(`Error deleting original file: ${unlinkErr}`);
                                }
                            });
                            resolve();
                        }
                    });
                });
            });
        }).on('error', (err) => {
            fs.unlink(outputPath, () => {}); // Delete the file if there was an error
            reject(`Error downloading image: ${err.message}`);
        });
    });
};

// Function to process all warframe images
const processWarframeImages = async () => {
    for (const warframe of warframes) {
        // Construct the correct image URL using the CDN
        const imageUrl = `https://cdn.warframestat.us/img/${warframe.imageName}`;
        const outputPath = path.join('/hdd1/warframe/data/warframes', warframe.imageName);

        try {
            await downloadAndConvertToWebp(imageUrl, outputPath);
            console.log(`Converted ${warframe.name} image to .webp`);
        } catch (error) {
            console.error(`Failed to process ${warframe.name}: ${error}`);
        }
    }
};

processWarframeImages();

