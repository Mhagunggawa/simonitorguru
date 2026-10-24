/**
 * Utility untuk kompresi foto kamera ke target ukuran ~100KB
 * untuk menghemat penyimpanan server dan mempercepat waktu upload.
 */

export interface CompressOptions {
    targetSizeKB?: number; // Target ukuran dalam KB (default: 100)
    maxDimension?: number; // Dimensi maksimum width/height (default: 960)
    minQuality?: number;   // Kualitas terendah JPEG (default: 0.25)
    maxQuality?: number;   // Kualitas tertinggi JPEG (default: 0.85)
    fileName?: string;     // Nama file output (default: timestamp)
}

export interface CompressResult {
    file: File;
    blob: Blob;
    previewUrl: string;
    sizeKB: number;
    formattedSize: string;
}

/**
 * Mengubah canvas menjadi Blob dengan format image/jpeg pada kualitas tertentu.
 */
function canvasToBlob(canvas: HTMLCanvasElement, quality: number): Promise<Blob | null> {
    return new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(blob), 'image/jpeg', quality);
    });
}

/**
 * Membuat canvas baru dengan ukuran yang diskalakan agar tidak melebihi maxDimension.
 */
function createScaledCanvas(source: HTMLCanvasElement, maxDimension: number): HTMLCanvasElement {
    let width = source.width;
    let height = source.height;

    if (Math.max(width, height) > maxDimension) {
        const scale = maxDimension / Math.max(width, height);
        width = Math.round(width * scale);
        height = Math.round(height * scale);
    }

    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, width);
    canvas.height = Math.max(1, height);

    const ctx = canvas.getContext('2d');
    if (ctx) {
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(source, 0, 0, width, height);
    }

    return canvas;
}

/**
 * Kompresi konten canvas ke ukuran target (~100KB) dengan kualitas visual terbaik.
 */
export async function compressCanvasToTargetSize(
    sourceCanvas: HTMLCanvasElement,
    options: CompressOptions = {}
): Promise<CompressResult> {
    const targetSizeKB = options.targetSizeKB ?? 100;
    const targetBytes = targetSizeKB * 1024; // 102,400 bytes
    const maxDimension = options.maxDimension ?? 960;
    const minQuality = options.minQuality ?? 0.25;
    const maxQuality = options.maxQuality ?? 0.85;
    const fileName = options.fileName ?? `foto_${Date.now()}.jpg`;

    let currentCanvas = createScaledCanvas(sourceCanvas, maxDimension);

    let bestBlob: Blob | null = null;

    // 1. Coba pada maxQuality terlebih dahulu
    const blobMax = await canvasToBlob(currentCanvas, maxQuality);
    if (blobMax && blobMax.size <= targetBytes) {
        // Jika pada kualitas terbaik ukurannya sudah <= targetBytes (~100KB), gunakan ini
        bestBlob = blobMax;
    } else {
        // 2. Binary search mencari kualitas tertinggi yang tetap <= targetBytes (atau mendekati targetBytes)
        let low = minQuality;
        let high = maxQuality;

        for (let i = 0; i < 5; i++) {
            const mid = (low + high) / 2;
            const blob = await canvasToBlob(currentCanvas, mid);
            if (!blob) break;

            if (blob.size <= targetBytes) {
                bestBlob = blob;
                // Masih ada ruang untuk meningkatkan kualitas mendekati 100KB
                low = mid;
            } else {
                // Ukuran melebihi target, turunkan kualitas
                high = mid;
            }
        }

        // 3. Jika scene sangat kompleks dan masih melebihi targetBytes, turunkan dimensi resolusi
        if (!bestBlob || bestBlob.size > targetBytes * 1.05) {
            const fallbackDimensions = [720, 600, 480];
            for (const dim of fallbackDimensions) {
                currentCanvas = createScaledCanvas(sourceCanvas, dim);
                const testBlob = await canvasToBlob(currentCanvas, 0.65);
                if (testBlob && testBlob.size <= targetBytes) {
                    bestBlob = testBlob;
                    break;
                }
                const testMinBlob = await canvasToBlob(currentCanvas, 0.4);
                if (testMinBlob && testMinBlob.size <= targetBytes) {
                    bestBlob = testMinBlob;
                    break;
                }
            }
        }
    }

    // Fallback jika somehow null
    if (!bestBlob) {
        bestBlob = (await canvasToBlob(currentCanvas, 0.5)) || new Blob([], { type: 'image/jpeg' });
    }

    const file = new File([bestBlob], fileName, {
        type: 'image/jpeg',
        lastModified: Date.now(),
    });

    const sizeKB = Number((bestBlob.size / 1024).toFixed(1));
    const formattedSize = `${sizeKB} KB`;
    const previewUrl = URL.createObjectURL(bestBlob);

    return {
        file,
        blob: bestBlob,
        previewUrl,
        sizeKB,
        formattedSize,
    };
}
