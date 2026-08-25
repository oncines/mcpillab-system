import { initializeApp, getApps, getApp } from 'firebase/app';
import {
  getFirestore,
  collection,
  doc,
  setDoc,
  getDocs,
  onSnapshot,
  updateDoc,
  deleteDoc,
  serverTimestamp,
  type Firestore,
} from 'firebase/firestore';

// Firebase Client Configuration
// Uses environment variables if configured or standard defaults for cloud-hosted Firestore
const firebaseConfig = {
  apiKey: import.meta.env.VITE_FIREBASE_API_KEY || 'AIzaSyDemoKeyMCPILChemicalLabCloud2026',
  authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN || 'mcpil-lab-system.firebaseapp.com',
  projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID || 'mcpil-lab-system',
  storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET || 'mcpil-lab-system.appspot.com',
  messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID || '1029384756',
  appId: import.meta.env.VITE_FIREBASE_APP_ID || '1:1029384756:web:8a9b0c1d2e3f4g5h',
};

// Initialize Firebase safely (avoid multiple initializations)
export const app = getApps().length === 0 ? initializeApp(firebaseConfig) : getApp();

// Initialize Firestore instance
export const db: Firestore = getFirestore(app);

// Firestore Collection Names
export const COLLECTIONS = {
  USERS: 'users',
  INVENTORY: 'inventory',
  PURCHASE_ORDERS: 'purchase_orders',
  ATTENDANCE: 'attendance_records',
  DELIVERIES: 'deliveries',
  INVOICES: 'invoices',
  NOTIFICATIONS: 'notifications',
  AUDIT_LOGS: 'audit_logs',
} as const;

// Helper: Sync document to Firestore
export async function syncDocToFirestore(collectionName: string, docId: string | number, data: any) {
  try {
    const docRef = doc(db, collectionName, String(docId));
    await setDoc(docRef, { ...data, updatedAt: serverTimestamp() }, { merge: true });
    return { success: true };
  } catch (error: any) {
    console.warn(`Firestore sync warning for ${collectionName}/${docId}:`, error?.message || error);
    return { success: false, error: error?.message };
  }
}

// Helper: Delete document from Firestore
export async function deleteDocFromFirestore(collectionName: string, docId: string | number) {
  try {
    const docRef = doc(db, collectionName, String(docId));
    await deleteDoc(docRef);
    return { success: true };
  } catch (error: any) {
    console.warn(`Firestore delete warning for ${collectionName}/${docId}:`, error?.message || error);
    return { success: false, error: error?.message };
  }
}

// Helper: Fetch all documents from a Firestore collection
export async function fetchCollectionFromFirestore<T>(collectionName: string): Promise<T[]> {
  try {
    const colRef = collection(db, collectionName);
    const snapshot = await getDocs(colRef);
    const results: T[] = [];
    snapshot.forEach((d) => {
      results.push({ id: d.id, ...d.data() } as T);
    });
    return results;
  } catch (error: any) {
    console.warn(`Firestore fetch warning for ${collectionName}:`, error?.message || error);
    return [];
  }
}

// Helper: Subscribe to real-time changes
export function subscribeToCollection<T>(
  collectionName: string,
  onUpdate: (data: T[]) => void,
  onError?: (err: Error) => void
) {
  try {
    const colRef = collection(db, collectionName);
    return onSnapshot(
      colRef,
      (snapshot) => {
        const items: T[] = [];
        snapshot.forEach((d) => {
          items.push({ id: d.id, ...d.data() } as T);
        });
        onUpdate(items);
      },
      (error) => {
        if (onError) onError(error);
        else console.warn(`Firestore real-time subscription warning for ${collectionName}:`, error);
      }
    );
  } catch (err: any) {
    console.warn(`Could not set up subscription for ${collectionName}:`, err);
    return () => {};
  }
}
